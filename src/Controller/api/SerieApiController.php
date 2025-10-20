<?php

namespace App\Controller\api;

use App\Repository\SerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/series', name: 'api_series_')]
class SerieApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        SerieRepository $serieRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $sort = $request->query->get('sort');

        $series = $serieRepository->findAll(); // À adapter avec pagination

        return $this->json([
            'data' => $series,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($series)
            ]
        ], 200, [], ['groups' => ['serie:read']]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, SerieRepository $serieRepository): JsonResponse
    {
        $serie = $serieRepository->find($id);

        if (!$serie) {
            return $this->json(['error' => 'Série non trouvée'], 404);
        }

        return $this->json($serie, 200, [], ['groups' => ['serie:read', 'serie:detail']]);
    }
}