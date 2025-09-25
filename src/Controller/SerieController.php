<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Form\StreamingLinkType;
use App\Repository\ContributorRepository;
use App\Repository\GenreRepository;
use App\Repository\SerieRepository;
use App\Service\SerieService;
use App\Utils\FileManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class SerieController extends AbstractController
{
    #[Route('/api/series', name: 'api_serie_list', methods: ['GET'])]
    public function serieList(
        SerieRepository $serieRepository,
        Request $request,
        PaginatorInterface $paginator,
        ParameterBagInterface $parameterBag,
        SessionInterface $session
    ): JsonResponse {
        $sort    = $request->query->get('sort');
        $search  = $request->query->get('search');
        $genreId = $request->query->getInt('genre', 0);
        $type    = $request->query->get('type');
        $ignoredIds = array_keys($session->get('ignored_series', []));

        $query = $serieRepository->getQueryForSeries($sort, $search, $ignoredIds, $genreId, $type);

        $series = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $parameterBag->get('serie')['nb_par_page']
        );

        $host = $request->getSchemeAndHttpHost();

        $data = [];
        foreach ($series as $s) {
            $data[] = [
                'id'           => $s->getId(),
                'name'         => $s->getName(),
                'overview'     => $s->getOverview(),
                'vote'         => $s->getVote(),
                'country'      => $s->getCountry(),
                'poster'       => $s->getPoster()
                    ? $host . '/uploads/posters/series/' . $s->getPoster()
                    : null,
                'backdrop'     => $s->getBackdrop()
                    ? $host . '/uploads/backdrops/' . $s->getBackdrop()
                    : null,
                'firstAirDate' => $s->getFirstAirDate()?->format('Y-m-d'),
                'lastAirDate'  => $s->getLastAirDate()?->format('Y-m-d'),
            ];
        }

        return new JsonResponse([
            'items' => $data,
            'total' => $series->getTotalItemCount(),
        ]);
    }


    #[Route('/show/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Serie $serie, ContributorRepository $contributorRepository): Response
    {
        $contributors = $contributorRepository->findBySerie($serie);
        $watchLinks   = $this->buildWatchLinks($serie);

        return $this->render('serie/show.html.twig', [
            'serie'        => $serie,
            'contributors' => $contributors,
            'watchLinks'   => $watchLinks,
        ]);
    }

    #[Route('/api/series/{id}', name: 'api_serie_detail', methods: ['GET'])]
    public function serieDetail(Serie $serie, Request $request): JsonResponse
    {
        $data = [
            'id'           => $serie->getId(),
            'name'         => $serie->getName(),
            'originalName' => $serie->getOriginalName(),
            'overview'     => $serie->getOverview(),
            'status'       => $serie->getStatus(),
            'vote'         => $serie->getVote(),
            'popularity'   => $serie->getPopularity(),
            'poster' => $serie->getPoster()
                ? $request->getSchemeAndHttpHost() . '/uploads/posters/series/' . $serie->getPoster()
                : null,
            'backdrop' => $serie->getBackdrop()
                ? $request->getSchemeAndHttpHost() . '/uploads/backdrops/' . $serie->getBackdrop()
                : null,
            'country'      => $serie->getCountry(),
            'firstAirDate' => $serie->getFirstAirDate()?->format('Y-m-d'),
            'lastAirDate'  => $serie->getLastAirDate()?->format('Y-m-d'),

            'genres' => array_map(
                fn($g) => $g->getName(),
                $serie->getGenres()->toArray()
            ),
            'contributors' => array_map(
                fn($c) => [
                    'id'   => $c->getId(),
                    'name' => $c->getName(),
                    'role' => $c->getRole(),
                ],
                $serie->getContributors()->toArray()
            ),
            'streamingLinks' => $serie->getStreamingLinks() ?? [],
        ];

        return new JsonResponse($data);
    }

    #[Route('/favorite/{id}', name: 'favorite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function favorite(Serie $serie, EntityManagerInterface $em, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['ok' => false, 'redirect' => $this->generateUrl('app_login')], 401);
        }

        if ($user->hasFavoriteSerie($serie)) {
            $user->removeFavoriteSerie($serie);
            $status = 'removed';
        } else {
            $user->addFavoriteSerie($serie);
            $status = 'added';
        }

        $em->flush();

        return new JsonResponse(['ok' => true, 'status' => $status]);
    }

    #[Route('/random', name: 'random', methods: ['GET'])]
    public function random(SerieService $serieService): Response
    {
        $user  = $this->getUser();
        $serie = $serieService->getRandomSerie($user);

        if (!$serie) {
            $this->addFlash('info', 'Aucune série disponible.');
            return $this->redirectToRoute('serie_liste');
        }

        return $this->redirectToRoute('serie_show', ['id' => $serie->getId()]);
    }

    #[Route('/suggest', name: 'suggest', methods: ['GET'])]
    public function suggest(Request $request, SerieRepository $repo): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return new JsonResponse([]);
        }

        // Recherche uniquement par titre
        $series = $repo->createQueryBuilder('s')
            ->where('s.name LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = array_map(fn(Serie $s) => [
            'id'     => $s->getId(),
            'title'  => $s->getName(),
            'year'   => $s->getFirstAirDate()?->format('Y')
                . ($s->getLastAirDate() ? ' – '.$s->getLastAirDate()->format('Y') : ''),
            'poster' => $s->getPoster() ? '/uploads/posters/series/'.$s->getPoster() : null,
            'url'    => $this->generateUrl('serie_detail', ['id' => $s->getId()]),
        ], $series);

        return new JsonResponse($results);
    }

    #[Route('/ignore/{id}', name: 'ignore', requirements: ['id' => '\d+'])]
    public function ignore(Serie $serie, Request $request, SessionInterface $session): Response
    {
        $ignored = $session->get('ignored_series', []);
        $ignored[$serie->getId()] = true;
        $session->set('ignored_series', $ignored);

        $this->addFlash('info', sprintf('« %s » a été masquée.', $serie->getName()));
        return $this->redirectToRoute('serie_liste', $request->query->all());
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $em, FileManager $fileManager): Response
    {
        $serie = new Serie();
        $form  = $this->createForm(SerieType::class, $serie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file   = $form->get('backdrop_file')->getData();
            $poster = $form->get('poster_file')->getData();

            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops', $serie->getName())) {
                    $serie->setBackdrop($name);
                }
            }
            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->upload($poster, 'uploads/posters/series', $serie->getName())) {
                    $serie->setPoster($name);
                }
            }

            $em->persist($serie);
            $em->flush();

            $this->addFlash('success', 'Votre série a bien été enregistrée !');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        return $this->render('serie/edit.html.twig', [
            'serieForm' => $form,
            'is_edit'   => false,
            'watchLinks' => [],
            'streamingLinks' => []
        ]);
    }

    #[Route('/update/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function update(Request $request, EntityManagerInterface $em, Serie $serie, FileManager $fileManager): Response
    {
        $form = $this->createForm(SerieType::class, $serie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('backdrop_file')->getData();
            if ($file instanceof UploadedFile) {
                if ($name = $fileManager->upload($file, 'uploads/backdrops/', $serie->getName(), $serie->getBackdrop())) {
                    $serie->setBackdrop($name);
                }
            }

            $poster = $form->get('poster_file')->getData();
            if ($poster instanceof UploadedFile) {
                if ($name = $fileManager->uploadPoster($poster, 'uploads/posters/series', $serie->getName(), $serie->getPoster())) {
                    $serie->setPoster($name);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Une série a été modifiée avec succès.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        $watchLinks = $this->buildWatchLinks($serie);
        $linkForm = $this->createForm(StreamingLinkType::class, null, [
            'action' => $this->generateUrl('serie_link_add', ['id' => $serie->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('serie/edit.html.twig', [
            'serieForm' => $form,
            'is_edit'   => true,
            'serie' => $serie,
            'watchLinks' => $watchLinks,
            'streamingLinks' => $serie->getStreamingLinks() ?? [],
            'linkForm' => $linkForm
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', requirements: ['id' => '\d+'])]
    public function delete(Request $request, Serie $serie, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $serie->getId(), $request->get('token'))) {
            $em->remove($serie);
            $em->flush();
            $this->addFlash('success', 'La série a bien été supprimée.');
        } else {
            $this->addFlash('danger', 'Problème lors de la suppression.');
        }

        return $this->redirectToRoute('serie_liste');
    }

    #[Route('/{id}/streaming-link/add', name: 'link_add', methods: ['POST'])]
    public function addStreamingLink(Serie $serie, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(StreamingLinkType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['ok' => false, 'message' => 'Formulaire invalide.'], 400);
            }
            $this->addFlash('danger', 'Formulaire invalide.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        $data  = $form->getData();
        $links = $serie->getStreamingLinks() ?? [];

        $links[] = [
            'provider' => strtolower((string) $data['provider']),
            'url'      => (string) $data['url'],
            'enabled'  => !empty($data['enabled']),
        ];

        $serie->setStreamingLinks($links);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('serie/_watch_links.html.twig', [
                'serie'      => $serie,
                'links'      => $serie->getStreamingLinks() ?? [],
                'watchLinks' => $this->buildWatchLinks($serie),
            ]);
            return new JsonResponse(['ok' => true, 'html' => $html]);
        }

        $this->addFlash('success', 'Lien ajouté.');
        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }

    #[Route('/{id}/streaming-link/{index}/delete', name: 'link_delete', requirements: ['index' => '\d+'], methods: ['POST'])]
    public function deleteStreamingLink(Serie $serie, int $index, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('del_link_'.$serie->getId().'_'.$index, $request->request->get('_token'))) {
            return $request->isXmlHttpRequest()
                ? new JsonResponse(['ok' => false, 'message' => 'CSRF invalide.'], 403)
                : $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        $links = $serie->getStreamingLinks() ?? [];
        if (isset($links[$index])) {
            array_splice($links, $index, 1);
            $serie->setStreamingLinks(array_values($links));
            $em->flush();
        }

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('serie/_watch_links.html.twig', [
                'serie'      => $serie,
                'links'      => $serie->getStreamingLinks() ?? [],
                'watchLinks' => $this->buildWatchLinks($serie),
            ]);
            return new JsonResponse(['ok' => true, 'html' => $html]);
        }

        $this->addFlash('success', 'Lien supprimé.');
        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }
}

