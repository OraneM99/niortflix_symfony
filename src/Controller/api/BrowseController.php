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
     * Page d'accueil découverte
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('browse/index.html.twig', [
            'tmdb_trending' => $this->serieManager->getTrendingSeries(6),
            'tmdb_top_rated' => $this->serieManager->getTopRatedSeries(6),
            'tmdb_popular' => $this->serieManager->getPopularSeriesFromApi(1, 6),
        ]);
    }

    /**
     * Séries tendances
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
     * Séries populaires
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
     * Meilleures séries
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

        return $this->render('browse/detail.html.twig', [
            'serie' => $serie,
        ]);
    }

    /**
     * Recherche
     */
    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $data = $this->serieManager->searchSeries($query, 20);
            $results = $data['api'] ?? [];
        }

        return $this->render('browse/search.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}