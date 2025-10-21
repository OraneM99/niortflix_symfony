<?php

namespace App\Controller\api;

use App\Service\SerieManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/series', name: 'api_series_')]
class BrowseApiController extends AbstractController
{
    public function __construct(
        private readonly SerieManagerService $serieManager
    ) {}

    #[Route('/home', name: 'home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        return $this->json([
            'trending' => $this->serieManager->getTrendingSeries(6),
            'top_rated' => $this->serieManager->getTopRatedSeries(6),
            'popular' => $this->serieManager->getPopularSeriesFromApi(1, 6),
        ]);
    }

    #[Route('/trending', name: 'trending', methods: ['GET'])]
    public function trending(): JsonResponse
    {
        return $this->json($this->serieManager->getTrendingSeries(20));
    }

    #[Route('/popular', name: 'popular', methods: ['GET'])]
    public function popular(): JsonResponse
    {
        return $this->json($this->serieManager->getPopularSeriesFromApi(1, 20));
    }

    #[Route('/top-rated', name: 'top_rated', methods: ['GET'])]
    public function topRated(): JsonResponse
    {
        return $this->json($this->serieManager->getTopRatedSeries(20));
    }

    #[Route('/detail/{tmdbId}', name: 'detail', methods: ['GET'])]
    public function detail(int $tmdbId): JsonResponse
    {
        $serie = $this->serieManager->getSerieDetails($tmdbId);
        if (!$serie) {
            return $this->json(['error' => 'Série introuvable'], 404);
        }

        return $this->json($serie);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(): JsonResponse
    {
        $query = $_GET['q'] ?? '';
        $results = [];

        if (strlen($query) >= 2) {
            $data = $this->serieManager->searchSeries($query, 20);
            $results = $data['api'] ?? [];
        }

        return $this->json([
            'query' => $query,
            'results' => $results,
        ]);
    }
}
