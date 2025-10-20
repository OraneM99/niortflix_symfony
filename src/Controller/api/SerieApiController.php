<?php

namespace App\Controller\api;

use App\Service\SerieManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/series', name: 'api_series_')]
class SerieApiController extends AbstractController
{
    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {
    }

    /**
     * Page d'accueil - données mixtes
     */
    #[Route('/home', name: 'home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        $data = [
            'trending' => $this->serieManager->getTrendingSeries(6),
            'top_rated' => $this->serieManager->getTopRatedSeries(6),
            'popular' => $this->serieManager->getPopularSeriesFromApi(1, 6),
        ];

        return $this->json($data);
    }

    /**
     * Séries tendances
     */
    #[Route('/trending', name: 'trending', methods: ['GET'])]
    public function trending(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getTrendingSeries($limit);

        return $this->json([
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Séries populaires
     */
    #[Route('/popular', name: 'popular', methods: ['GET'])]
    public function popular(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getPopularSeriesFromApi($page, $limit);

        return $this->json([
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Meilleures séries
     */
    #[Route('/top-rated', name: 'top_rated', methods: ['GET'])]
    public function topRated(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $series = $this->serieManager->getTopRatedSeries($limit);

        return $this->json([
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Détails d'une série
     */
    #[Route('/detail/{tmdbId}', name: 'detail', methods: ['GET'])]
    public function detail(int $tmdbId): JsonResponse
    {
        $serie = $this->serieManager->getSerieDetails($tmdbId);

        if (!$serie) {
            return $this->json(['error' => 'Série introuvable'], 404);
        }

        return $this->json($serie);
    }

    /**
     * Recherche
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json(['data' => []]);
        }

        $results = $this->serieManager->searchSeries($query, 20);

        return $this->json([
            'data' => $results['api'] ?? []
        ]);
    }
}