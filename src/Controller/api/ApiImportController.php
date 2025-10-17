<?php

namespace App\Controller\api;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Serie;
use App\Repository\SerieRepository;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiImportController extends AbstractController
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly EntityManagerInterface $em,
        private readonly SerieRepository $serieRepository
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
     * Importe une série depuis TMDb
     */
    #[Route('/import/{tmdbId}', name: 'import', requirements: ['tmdbId' => '\d+'], methods: ['POST'])]
    public function import(int $tmdbId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifie si déjà en base
        $existing = $this->serieRepository->findOneBy(['tmdbId' => $tmdbId]);
        if ($existing) {
            $this->addFlash('warning', sprintf('La série "%s" existe déjà.', $existing->getName()));
            return $this->redirectToRoute('serie_detail', ['id' => $existing->getId()]);
        }

        // Récupère depuis TMDb
        $data = $this->tmdbService->getSerie($tmdbId);
        if (!$data) {
            $this->addFlash('danger', 'Impossible de récupérer les données depuis TMDb.');
            return $this->redirectToRoute('api_search');
        }

        // Crée la série
        $serie = new Serie();
        $serie->setTmdbId($tmdbId)
            ->setName($data['name'] ?? 'Sans titre')
            ->setOverview($data['overview'] ?? '')
            ->setStatus($this->mapStatus($data['status'] ?? ''))
            ->setVote($data['vote_average'] ?? 0)
            ->setPopularity($data['popularity'] ?? 0)
            ->setCountry($data['origin_country'][0] ?? null);

        // Dates
        if (!empty($data['first_air_date'])) {
            $serie->setFirstAirDate(new \DateTime($data['first_air_date']));
        }
        if (!empty($data['last_air_date'])) {
            $serie->setLastAirDate(new \DateTime($data['last_air_date']));
        }

        // Images
        if (!empty($data['poster_path'])) {
            $serie->setPoster($this->tmdbService->getImageUrl($data['poster_path']));
        }
        if (!empty($data['backdrop_path'])) {
            $serie->setBackdrop($this->tmdbService->getImageUrl($data['backdrop_path'], 'w1280'));
        }

        // Streaming providers
        $providers = $this->tmdbService->getStreamingProviders($data);
        $streamingLinks = [];
        foreach ($providers as $provider) {
            $streamingLinks[] = [
                'provider' => $provider['name'],
                'url' => '', // À remplir manuellement
                'enabled' => false
            ];
        }
        $serie->setStreamingLinks($streamingLinks);

        $this->em->persist($serie);
        $this->em->flush();

        $this->addFlash('success', sprintf('La série "%s" a été importée avec succès !', $serie->getName()));
        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }

    /**
     * Importe les saisons et épisodes d'une série
     */
    #[Route('/import-episodes/{id}', name: 'import_episodes', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function importEpisodes(Serie $serie): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$serie->getTmdbId()) {
            $this->addFlash('danger', 'Cette série n\'a pas d\'ID TMDb.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        $data = $this->tmdbService->getSerie($serie->getTmdbId());
        if (!$data || empty($data['seasons'])) {
            $this->addFlash('danger', 'Impossible de récupérer les saisons.');
            return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
        }

        $imported = 0;
        foreach ($data['seasons'] as $seasonData) {
            // Ignore la saison 0 (spéciaux)
            if ($seasonData['season_number'] === 0) {
                continue;
            }

            $seasonDetails = $this->tmdbService->getSeason(
                $serie->getTmdbId(),
                $seasonData['season_number']
            );

            if (!$seasonDetails) {
                continue;
            }

            // Crée la saison
            $season = new Season();
            $season->setSeasonNumber($seasonData['season_number'])
                ->setName($seasonData['name'] ?? 'Saison ' . $seasonData['season_number'])
                ->setOverview($seasonData['overview'] ?? null)
                ->setEpisodeCount(count($seasonDetails['episodes'] ?? []))
                ->setPosterPath($this->tmdbService->getImageUrl($seasonData['poster_path']));

            if (!empty($seasonData['air_date'])) {
                $season->setAirDate(new \DateTime($seasonData['air_date']));
            }

            $serie->addSeason($season);

            // Crée les épisodes
            foreach ($seasonDetails['episodes'] ?? [] as $episodeData) {
                $episode = new Episode();
                $episode->setEpisodeNumber($episodeData['episode_number'])
                    ->setName($episodeData['name'] ?? 'Episode ' . $episodeData['episode_number'])
                    ->setOverview($episodeData['overview'] ?? null)
                    ->setRuntime($episodeData['runtime'] ?? null)
                    ->setTmdbId($episodeData['id'] ?? null)
                    ->setStillPath($this->tmdbService->getImageUrl($episodeData['still_path']));

                if (!empty($episodeData['air_date'])) {
                    $episode->setAirDate(new \DateTime($episodeData['air_date']));
                }

                $season->addEpisode($episode);
                $imported++;
            }
        }

        $this->em->flush();

        $this->addFlash('success', sprintf('%d épisodes importés avec succès !', $imported));
        return $this->redirectToRoute('serie_detail', ['id' => $serie->getId()]);
    }

    /**
     * Recherche en AJAX
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
                'poster' => $this->tmdbService->getImageUrl($item['poster_path'], 'w200'),
            ];
        }

        return $this->json($results);
    }

    /**
     * Convertit le statut TMDb en statut local
     */
    private function mapStatus(string $tmdbStatus): string
    {
        return match ($tmdbStatus) {
            'Returning Series' => 'returning',
            'Ended' => 'ended',
            'Canceled' => 'cancelled',
            default => 'returning'
        };
    }
}