<?php

namespace App\Controller\api;

use App\Service\SerieManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SerieApiController extends AbstractController
{
    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {
    }

    /**
     * ============================================
     * PAGE D'ACCUEIL - PUBLIQUE
     * ============================================
     */
    #[Route('/api/home', name: 'api_home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        $data = [
            'success' => true,
            'trending' => $this->serieManager->getTrendingSeries(6),
            'top_rated' => $this->serieManager->getTopRatedSeries(6),
            'popular' => $this->serieManager->getPopularSeriesFromApi(1, 6),
        ];

        return $this->json($data);
    }

    /**
     * ============================================
     * ROUTES PROTÉGÉES - Préfixe /api/series
     * ============================================
     */

    /**
     * Séries tendances - PROTÉGÉ
     * Route: /api/series/trending
     */
    #[Route('/api/series/trending', name: 'api_series_trending', methods: ['GET'])]
    public function trending(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getTrendingSeries($limit);

        return $this->json([
            'success' => true,
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($series)
            ]
        ]);
    }

    /**
     * Séries populaires - PROTÉGÉ
     * Route: /api/series/popular
     */
    #[Route('/api/series/popular', name: 'api_series_popular', methods: ['GET'])]
    public function popular(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getPopularSeriesFromApi($page, $limit);

        return $this->json([
            'success' => true,
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($series)
            ]
        ]);
    }

    /**
     * Meilleures séries - PROTÉGÉ
     * Route: /api/series/top-rated
     */
    #[Route('/api/series/top-rated', name: 'api_series_top_rated', methods: ['GET'])]
    public function topRated(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getTopRatedSeries($limit);

        return $this->json([
            'success' => true,
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($series)
            ]
        ]);
    }

    /**
     * Détails d'une série - PROTÉGÉ
     * Route: /api/series/detail/{tmdbId}
     */
    #[Route('/api/series/detail/{tmdbId}', name: 'api_series_detail', methods: ['GET'])]
    public function detail(int $tmdbId): JsonResponse
    {
        $serie = $this->serieManager->getSerieDetails($tmdbId);

        if (!$serie) {
            return $this->json([
                'success' => false,
                'error' => 'Série introuvable'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $serie
        ]);
    }

    /**
     * Recherche - PROTÉGÉ
     * Route: /api/series/search?q=query
     */
    #[Route('/api/series/search', name: 'api_series_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([
                'success' => true,
                'query' => $query,
                'data' => [],
                'message' => 'Veuillez entrer au moins 2 caractères'
            ]);
        }

        $results = $this->serieManager->searchSeries($query, 20);

        return $this->json([
            'success' => true,
            'query' => $query,
            'data' => $results['api'] ?? [],
            'total' => count($results['api'] ?? [])
        ]);
    }
}