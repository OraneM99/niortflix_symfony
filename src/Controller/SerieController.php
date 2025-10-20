<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Form\StreamingLinkType;
use App\Repository\SerieRepository;
use App\Service\SerieManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

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

