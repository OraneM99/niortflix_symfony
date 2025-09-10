<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Form\StreamingLinkType;
use App\Repository\ContributorRepository;
use App\Repository\GenreRepository;
use App\Repository\SerieRepository;
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

#[Route('/serie', name: 'serie_')]
final class SerieController extends AbstractController
{
    /** Métadonnées d’icônes/labels pour les plateformes. */
    private const PROVIDER_META = [
        'adn'         => ['label' => 'ADN',     'icon' => 'icons/providers/ad .png'],
        'appletv'     => ['label' => 'Apple TV+',   'icon' => 'icons/providers/apple.png'],
        'canal'       => ['label' => 'Canal+',      'icon' => 'icons/providers/canal.jpg'],
        'crunchyroll' => ['label' => 'Crunchyroll', 'icon' => 'icons/providers/crunchyroll.png'],
        'disney'      => ['label' => 'Disney+',     'icon' => 'icons/providers/disney.webp'],
        'hbo'         => ['label' => 'HBO',     'icon' => 'icons/providers/hbo.png'],
        'netflix'     => ['label' => 'Netflix',     'icon' => 'icons/providers/netflix.png'],
        'paramount'   => ['label' => 'Paramount',     'icon' => 'icons/providers/paramount.png'],
        'prime'       => ['label' => 'Prime Video', 'icon' => 'icons/providers/prime.png'],
        'youtube'     => ['label' => 'Youtube', 'icon' => 'icons/providers/youtube.jpeg']
    ];

    /** Transforme les données brutes (JSON en base) en liens affichables. */
    private function buildWatchLinks(Serie $serie): array
    {
        $watchLinks = [];
        foreach (($serie->getStreamingLinks() ?? []) as $l) {
            if (!is_array($l) || empty($l['enabled']) || empty($l['provider']) || empty($l['url'])) {
                continue;
            }
            $key  = strtolower((string) $l['provider']);
            $meta = self::PROVIDER_META[$key] ?? ['label' => ucfirst($key), 'icon' => 'icons/providers/generic.png'];
            $watchLinks[] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'url' => (string) $l['url']];
        }
        return $watchLinks;
    }

    #[Route('/liste', name: 'liste')]
    public function liste(
        SerieRepository $serieRepository,
        GenreRepository $genreRepository,
        PaginatorInterface $paginator,
        Request $request,
        ParameterBagInterface $parameterBag,
        SessionInterface $session
    ): Response {
        $sort   = $request->query->get('sort');
        $search = $request->query->get('search');

        $ignoredIds = array_keys($session->get('ignored_series', []));

        $query = $serieRepository->getQueryForSeries($sort, $search, $ignoredIds);

        $series = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $parameterBag->get('serie')['nb_par_page']
        );

        $genres = $genreRepository->findAllOrderedByName();

        return $this->render('serie/liste.html.twig', [
            'series' => $series,
            'sort'   => $sort,
            'genres' => $genres,
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        Serie $serie,
        ContributorRepository $contributorRepository,
        Request $request,
        Environment $twig
    ): Response {
        $contributors = $contributorRepository->findBySerie($serie);
        $watchLinks   = $this->buildWatchLinks($serie);

        // Formulaire léger "Ajouter un lien" (affiché dans un modal)
        $linkForm = $this->createForm(StreamingLinkType::class, null, [
            'action' => $this->generateUrl('serie_link_add', ['id' => $serie->getId()]),
            'method' => 'POST',
        ]);

        // Mode fragment (injection dans le modal)
        if ($request->isXmlHttpRequest() || $request->query->getBoolean('partial')) {
            $tpl = $twig->load('serie/detail.html.twig');
            $context = [
                'serie'        => $serie,
                'contributors' => $contributors,
                'watchLinks'   => $watchLinks,
                'links'        => $serie->getStreamingLinks() ?? [],
                'linkForm'     => $linkForm->createView(),
            ];
            $body   = $tpl->renderBlock('body', $context);
            $styles = $tpl->hasBlock('stylesheets', $context) ? $tpl->renderBlock('stylesheets', $context) : '';

            $html = sprintf(
                '<div class="modal-header border-0">
                    <h5 class="modal-title text-white">%s</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                 </div>
                 <div class="modal-body">%s%s</div>',
                htmlspecialchars($serie->getName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $styles,
                $body
            );

            return new Response($html);
        }

        // Page complète
        return $this->render('serie/detail.html.twig', [
            'serie'        => $serie,
            'contributors' => $contributors,
            'watchLinks'   => $watchLinks,
            'links'        => $serie->getStreamingLinks() ?? [],
            'linkForm'     => $linkForm->createView(),
        ]);
    }

    #[Route('/favorite/{id}', name: 'favorite', requirements: ['id' => '\d+'], methods: ['GET','POST'])]
    public function favorite(Serie $serie, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();
        $isAjax = $request->isXmlHttpRequest() || str_contains((string) $request->headers->get('Accept'), 'application/json');

        if (!$user) {
            $payload = [
                'ok'       => false,
                'message'  => 'Vous devez être connecté pour ajouter une série aux favoris.',
                'redirect' => $this->generateUrl('app_login'),
            ];
            return $isAjax ? new JsonResponse($payload, 401) : $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('favorite' . $serie->getId(), $token)) {
                return $isAjax
                    ? new JsonResponse(['ok' => false, 'message' => 'Jeton CSRF invalide.'], 403)
                    : $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
            }
        }

        $favorited = !$user->hasFavoriteSerie($serie);
        if ($favorited) {
            $user->addFavoriteSerie($serie);
            $message = 'La série a été ajoutée à vos favoris.';
            $flash   = 'success';
        } else {
            $user->removeFavoriteSerie($serie);
            $message = 'La série a été retirée de vos favoris.';
            $flash   = 'info';
        }

        $em->flush();

        if ($isAjax) {
            return new JsonResponse(['ok' => true, 'favorited' => $favorited, 'message' => $message]);
        }

        $this->addFlash($flash, $message);
        $referer = $request->headers->get('referer') ?: $this->generateUrl('serie_liste');
        return $this->redirect($referer);
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

        return $this->render('serie/edit.html.twig', [
            'serieForm' => $form,
            'is_edit'   => true,
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
