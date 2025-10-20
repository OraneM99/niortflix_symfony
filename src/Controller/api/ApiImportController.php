<?php

namespace App\Controller\api;

use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiImportController extends AbstractController
{
    public function __construct(
        private readonly TmdbService $tmdbService
    ) {
    }

    /**
     * Page de recherche de séries sur TMDb
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $results = [];

        if (strlen($query) >= 2) {
            $data = $this->tmdbService->searchSerie($query);
            $results = $data['results'] ?? [];
        }

        return $this->render('api/search.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Recherche AJAX
     */
    #[Route('/search-ajax', name: 'search_ajax', methods: ['GET'])]
    public function searchAjax(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $data = $this->tmdbService->searchSerie($query);
        $results = [];

        foreach ($data['results'] ?? [] as $item) {
            $results[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'year' => isset($item['first_air_date']) ? substr($item['first_air_date'], 0, 4) : null,
                'overview' => $item['overview'] ?? '',
                'poster' => $this->tmdbService->getImageUrl($item['poster_path'] ?? null, 'w200'),
            ];
        }

        return $this->json($results);
    }
}