<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\SerieType;
use App\Form\StreamingLinkType;
use App\Repository\ContributorRepository;
use App\Repository\GenreRepository;
use App\Repository\SerieRepository;
use App\Service\SerieService;
use App\Service\SerieManagerService;
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
    private const PROVIDER_META = [
        'adn'         => ['label' => 'ADN',         'icon' => 'icons/providers/ad .png'],
        'appletv'     => ['label' => 'Apple TV+',   'icon' => 'icons/providers/apple.png'],
        'canal'       => ['label' => 'Canal+',      'icon' => 'icons/providers/canal.jpg'],
        'crunchyroll' => ['label' => 'Crunchyroll', 'icon' => 'icons/providers/crunchyroll.png'],
        'disney'      => ['label' => 'Disney+',     'icon' => 'icons/providers/disney.png'],
        'francetv'    => ['label' => 'France TV',   'icon' => 'icons/providers/francetv.webp'],
        'hbo'         => ['label' => 'HBO',         'icon' => 'icons/providers/hbo.jpg'],
        'm6'          => ['label' => 'M6+',         'icon' => 'icons/providers/m6.svg'],
        'netflix'     => ['label' => 'Netflix',     'icon' => 'icons/providers/netflix.png'],
        'primevideo'  => ['label' => 'Prime Video', 'icon' => 'icons/providers/primevideo.png'],
        'molotov'     => ['label' => 'Molotov',     'icon' => 'icons/providers/molotov.png'],
        'paramount'   => ['label' => 'Paramount',   'icon' => 'icons/providers/paramount.png'],
        'tf1'         => ['label' => 'TF1',         'icon' => 'icons/providers/tf1.webp'],
        'viki'        => ['label' => 'Viki',        'icon' => 'icons/providers/viki.png'],
        'warner'      => ['label' => 'Warner TV',   'icon' => 'icons/providers/warner.png'],
        'youtube'     => ['label' => 'Youtube',     'icon' => 'icons/providers/youtube.jpeg']
    ];

    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {
    }

    private function buildWatchLinks(Serie $serie): array
    {
        $out = [];
        foreach (($serie->getStreamingLinks() ?? []) as $l) {
            if (!is_array($l) || empty($l['enabled']) || empty($l['provider']) || empty($l['url'])) {
                continue;
            }

            $key = strtolower(preg_replace('/[^a-z0-9]+/','', (string)$l['provider']));
            $meta = self::PROVIDER_META[$key] ?? ['label' => ucfirst($key), 'icon' => 'icons/providers/generic.png'];

            $out[] = [
                'key'   => $key,
                'label' => $meta['label'],
                'icon'  => $meta['icon'],
                'url'   => (string)$l['url'],
            ];
        }
        return $out;
    }

    /**
     * Liste des séries - MAINTENANT HYBRIDE (BDD + API)
     */
    #[Route('/liste', name: 'liste')]
    public function liste(
        SerieRepository $serieRepository,
        GenreRepository $genreRepository,
        PaginatorInterface $paginator,
        Request $request,
        ParameterBagInterface $parameterBag,
        SessionInterface $session
    ): Response {
        $sort    = $request->query->get('sort');
        $search  = $request->query->get('search');
        $genreId = $request->query->getInt('genre', 0);
        $type    = $request->query->get('type');
        $source  = $request->query->get('source', 'local');
        $page    = $request->query->getInt('page', 1);

        $ignoredIds = array_keys($session->get('ignored_series', []));

        // Si source = 'api', on affiche depuis l'API
        if ($source === 'api') {
            $apiSeries = [];
            $totalPages = 1;

            // Selon le type demandé
            switch ($type) {
                case 'trending':
                    $data = $this->serieManager->getTrendingSeries(20, $page);
                    break;
                case 'popular':
                    $data = $this->serieManager->getPopularSeriesFromApi($page, 20);
                    break;
                case 'top_rated':
                    $data = $this->serieManager->getTopRatedSeries(20, $page);
                    break;
                default:
                    $data = $this->serieManager->getPopularSeriesFromApi($page, 20);
            }

            $apiSeries = $data['series'] ?? [];
            $totalPages = $data['total_pages'] ?? 1;

            return $this->render('serie/liste.html.twig', [
                'series'      => $apiSeries,
                'sort'        => $sort,
                'genres'      => $genreRepository->findAllOrderedByName(),
                'genreId'     => $genreId,
                'search'      => $search,
                'type'        => $type,
                'source'      => 'api',
                'is_api'      => true,
                'current_page' => $page,
                'total_pages'  => $totalPages,
            ]);
        }

        // Recherche mixte si search
        if ($search) {
            $results = $this->serieManager->searchSeries($search, 20);

            return $this->render('serie/liste.html.twig', [
                'series'     => $results['local'],
                'api_series' => $results['api'],
                'sort'       => $sort,
                'genres'     => $genreRepository->findAllOrderedByName(),
                'genreId'    => $genreId,
                'search'     => $search,
                'type'       => $type,
                'source'     => 'mixed',
                'is_api'     => false,
            ]);
        }

        // Sinon, liste locale classique
        $query = $serieRepository->getQueryForSeries($sort, $search, $ignoredIds, $genreId, $type);

        $series = $paginator->paginate(
            $query,
            $page,
            $parameterBag->get('serie')['nb_par_page']
        );

        $genres = $genreRepository->findAllOrderedByName();

        return $this->render('serie/liste.html.twig', [
            'series'  => $series,
            'sort'    => $sort,
            'genres'  => $genres,
            'genreId' => $genreId,
            'search'  => $search,
            'type'    => $type,
            'source'  => 'local',
            'is_api'  => false,
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

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        Serie $serie,
        ContributorRepository $contributorRepository,
        Request $request,
        Environment $twig
    ): Response {
        $contributors = $contributorRepository->findBySerie($serie);
        $watchLinks   = $this->buildWatchLinks($serie);

        $linkForm = $this->createForm(StreamingLinkType::class, null, [
            'action' => $this->generateUrl('serie_link_add', ['id' => $serie->getId()]),
            'method' => 'POST',
        ]);

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('partial')) {
            $tpl = $twig->load('serie/detail.html.twig');
            $context = [
                'serie'        => $serie,
                'contributors' => $contributors,
                'watchLinks'   => $watchLinks,
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
                $styles, $body
            );
            return new Response($html);
        }

        return $this->render('serie/detail.html.twig', [
            'serie'        => $serie,
            'contributors' => $contributors,
            'watchLinks'   => $watchLinks,
            'linkForm'     => $linkForm->createView(),
        ]);
    }

    /**
     * NOUVELLE : Détail d'une série depuis l'API (avant import)
     */
    #[Route('/preview/{tmdbId}', name: 'preview', requirements: ['tmdbId' => '\d+'])]
    public function preview(int $tmdbId, Request $request): Response
    {
        $serieData = $this->serieManager->getSerieDetails($tmdbId);

        if (!$serieData) {
            $this->addFlash('danger', 'Série introuvable.');
            return $this->redirectToRoute('serie_liste', ['source' => 'api']);
        }

        $isInDatabase = $this->serieManager->isSerieInDatabase($tmdbId);

        return $this->render('serie/preview.html.twig', [
            'serie' => $serieData,
            'is_in_database' => $isInDatabase,
            'is_preview' => true,
        ]);
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

        // Recherche mixte
        $searchResults = $this->serieManager->searchSeries($q, 10);

        $results = [];

        // Séries locales
        foreach ($searchResults['local'] as $serie) {
            $results[] = [
                'id'     => $serie->getId(),
                'title'  => $serie->getName(),
                'year'   => $serie->getFirstAirDate()?->format('Y')
                    . ($serie->getLastAirDate() ? ' – '.$serie->getLastAirDate()->format('Y') : ''),
                'poster' => $serie->getPoster(),
                'url'    => $this->generateUrl('serie_detail', ['id' => $serie->getId()]),
                'source' => 'local',
            ];
        }

        // Séries API
        foreach ($searchResults['api'] as $serie) {
            $results[] = [
                'id'     => $serie['tmdb_id'],
                'title'  => $serie['name'],
                'year'   => $serie['year'] ?? '',
                'poster' => $serie['poster'],
                'url'    => $this->generateUrl('serie_preview', ['tmdbId' => $serie['tmdb_id']]),
                'source' => 'api',
            ];
        }

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

