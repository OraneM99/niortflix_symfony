<?php

namespace App\Controller\api;

use App\Service\SerieManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/browse', name: 'browse_')]
class BrowseController extends AbstractController
{
    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {
    }

    /**
     * Page de découverte des séries depuis l'API
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $data = $this->serieManager->getHomePageSeries();

        return $this->render('browse/index.html.twig', [
            'local_recent' => $data['local_recent'],
            'local_popular' => $data['local_popular'],
            'tmdb_trending' => $data['tmdb_trending'],
            'tmdb_top_rated' => $data['tmdb_top_rated'],
        ]);
    }

    /**
     * Séries tendances (uniquement API)
     */
    #[Route('/trending/{page}', name: 'trending', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function trending(int $page): Response
    {
        $series = $this->serieManager->getTrendingSeries(20);

        return $this->render('browse/list.html.twig', [
            'title' => 'Séries tendances',
            'series' => $series,
            'page' => $page,
            'type' => 'trending'
        ]);
    }

    /**
     * Séries populaires (uniquement API)
     */
    #[Route('/popular/{page}', name: 'popular', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function popular(int $page): Response
    {
        $series = $this->serieManager->getPopularSeriesFromApi($page, 20);

        return $this->render('browse/list.html.twig', [
            'title' => 'Séries populaires',
            'series' => $series,
            'page' => $page,
            'type' => 'popular'
        ]);
    }

    /**
     * Meilleures séries (uniquement API)
     */
    #[Route('/top-rated/{page}', name: 'top_rated', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function topRated(int $page): Response
    {
        $series = $this->serieManager->getTopRatedSeries(20);

        return $this->render('browse/list.html.twig', [
            'title' => 'Meilleures séries',
            'series' => $series,
            'page' => $page,
            'type' => 'top_rated'
        ]);
    }

    /**
     * Nouvelles sorties (uniquement API)
     */
    #[Route('/new-releases/{page}', name: 'new_releases', requirements: ['page' => '\d+'], defaults: ['page' => 1])]
    public function newReleases(int $page): Response
    {
        $series = $this->serieManager->getNewReleases(20);

        return $this->render('browse/list.html.twig', [
            'title' => 'Nouvelles sorties',
            'series' => $series,
            'page' => $page,
            'type' => 'new_releases'
        ]);
    }

    /**
     * Détails d'une série depuis l'API (modal ou page)
     */
    #[Route('/detail/{tmdbId}', name: 'detail', requirements: ['tmdbId' => '\d+'])]
    public function detail(int $tmdbId, Request $request): Response
    {
        $serie = $this->serieManager->getSerieDetails($tmdbId);

        if (!$serie) {
            $this->addFlash('danger', 'Série introuvable.');
            return $this->redirectToRoute('browse_index');
        }

        // Vérifie si la série est déjà en BDD
        $isInDatabase = $this->serieManager->isSerieInDatabase($tmdbId);

        // Si c'est une requête AJAX, retourne juste le contenu
        if ($request->isXmlHttpRequest()) {
            return $this->render('browse/_detail_modal.html.twig', [
                'serie' => $serie,
                'is_in_database' => $isInDatabase,
            ]);
        }

        return $this->render('browse/detail.html.twig', [
            'serie' => $serie,
            'is_in_database' => $isInDatabase,
        ]);
    }

    /**
     * Recherche mixte (BDD + API)
     */
    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $results = $this->serieManager->searchSeries($query, 20);
        }

        return $this->render('browse/search.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}