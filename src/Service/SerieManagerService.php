<?php

namespace App\Service;

use App\Entity\Serie;
use App\Repository\SerieRepository;
use Psr\Log\LoggerInterface;

class SerieManagerService
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly SerieRepository $serieRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Récupère les séries pour la page d'accueil
     * Mélange BDD locale + API TMDb
     */
    public function getHomePageSeries(): array
    {
        return [
            'local_recent' => $this->serieRepository->findRecentSeries(6),
            'local_popular' => $this->serieRepository->findPopularSeries(6),
            'tmdb_trending' => $this->getTrendingSeries(6),
            'tmdb_top_rated' => $this->getTopRatedSeries(6),
        ];
    }

    /**
     * Récupère les séries tendances depuis TMDb
     */
    public function getTrendingSeries(int $limit = 20): array
    {
        try {
            $data = $this->tmdbService->getTrendingSeries();
            return $this->formatSeriesFromTmdb($data['results'] ?? [], $limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur trending series: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les séries les mieux notées depuis TMDb
     */
    public function getTopRatedSeries(int $limit = 20): array
    {
        try {
            $data = $this->tmdbService->getTopRatedSeries();
            return $this->formatSeriesFromTmdb($data['results'] ?? [], $limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur top rated series: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les séries populaires depuis TMDb
     */
    public function getPopularSeriesFromApi(int $page = 1, int $limit = 20): array
    {
        try {
            $data = $this->tmdbService->getPopularSeries($page);
            return $this->formatSeriesFromTmdb($data['results'] ?? [], $limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur popular series: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les nouvelles sorties depuis TMDb
     */
    public function getNewReleases(int $limit = 20): array
    {
        try {
            $data = $this->tmdbService->getOnTheAir();
            return $this->formatSeriesFromTmdb($data['results'] ?? [], $limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur new releases: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Recherche mixte : BDD locale + API TMDb
     */
    public function searchSeries(string $query, int $limit = 20): array
    {
        // Recherche locale
        $localResults = $this->serieRepository->createQueryBuilder('s')
            ->where('LOWER(s.name) LIKE :query')
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Recherche API
        $apiResults = [];
        try {
            $data = $this->tmdbService->searchSerie($query);
            $apiResults = $this->formatSeriesFromTmdb($data['results'] ?? [], $limit);
        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche API: ' . $e->getMessage());
        }

        return [
            'local' => $localResults,
            'api' => $apiResults,
            'total' => count($localResults) + count($apiResults)
        ];
    }

    /**
     * Récupère les détails d'une série depuis TMDb si pas en BDD
     */
    public function getSerieDetails(int $tmdbId): ?array
    {
        // Vérifie d'abord en local
        $localSerie = $this->serieRepository->findOneBy(['tmdbId' => $tmdbId]);
        if ($localSerie) {
            return $this->formatLocalSerie($localSerie);
        }

        // Sinon récupère depuis l'API
        try {
            $data = $this->tmdbService->getSerie($tmdbId);
            return $this->formatSingleSerieFromTmdb($data);
        } catch (\Exception $e) {
            $this->logger->error('Erreur détails série: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formate les données TMDb pour affichage
     */
    private function formatSeriesFromTmdb(array $series, int $limit): array
    {
        $formatted = [];
        $count = 0;

        foreach ($series as $serie) {
            if ($count >= $limit) {
                break;
            }

            $formatted[] = [
                'tmdb_id' => $serie['id'],
                'name' => $serie['name'] ?? 'Titre inconnu',
                'overview' => $serie['overview'] ?? '',
                'poster' => $this->tmdbService->getImageUrl($serie['poster_path'] ?? null),
                'backdrop' => $this->tmdbService->getImageUrl($serie['backdrop_path'] ?? null, 'w1280'),
                'vote' => $serie['vote_average'] ?? 0,
                'popularity' => $serie['popularity'] ?? 0,
                'first_air_date' => $serie['first_air_date'] ?? null,
                'year' => isset($serie['first_air_date']) ? substr($serie['first_air_date'], 0, 4) : null,
                'is_local' => false, // Indique que c'est depuis l'API
                'genre_ids' => $serie['genre_ids'] ?? [],
            ];

            $count++;
        }

        return $formatted;
    }

    /**
     * Formate une série TMDb complète
     */
    private function formatSingleSerieFromTmdb(array $data): array
    {
        return [
            'tmdb_id' => $data['id'],
            'name' => $data['name'] ?? 'Titre inconnu',
            'overview' => $data['overview'] ?? '',
            'poster' => $this->tmdbService->getImageUrl($data['poster_path'] ?? null),
            'backdrop' => $this->tmdbService->getImageUrl($data['backdrop_path'] ?? null, 'w1280'),
            'vote' => $data['vote_average'] ?? 0,
            'popularity' => $data['popularity'] ?? 0,
            'first_air_date' => $data['first_air_date'] ?? null,
            'last_air_date' => $data['last_air_date'] ?? null,
            'status' => $data['status'] ?? '',
            'number_of_seasons' => $data['number_of_seasons'] ?? 0,
            'number_of_episodes' => $data['number_of_episodes'] ?? 0,
            'genres' => $data['genres'] ?? [],
            'is_local' => false,
        ];
    }

    /**
     * Formate une série locale pour uniformiser avec l'API
     */
    private function formatLocalSerie(Serie $serie): array
    {
        return [
            'id' => $serie->getId(),
            'tmdb_id' => $serie->getTmdbId(),
            'name' => $serie->getName(),
            'overview' => $serie->getOverview(),
            'poster' => $serie->getPoster(),
            'backdrop' => $serie->getBackdrop(),
            'vote' => $serie->getVote(),
            'popularity' => $serie->getPopularity(),
            'first_air_date' => $serie->getFirstAirDate()?->format('Y-m-d'),
            'last_air_date' => $serie->getLastAirDate()?->format('Y-m-d'),
            'status' => $serie->getStatus(),
            'is_local' => true, // Important : indique que c'est en BDD
        ];
    }

    /**
     * Vérifie si une série existe en local
     */
    public function isSerieInDatabase(int $tmdbId): bool
    {
        return $this->serieRepository->findOneBy(['tmdbId' => $tmdbId]) !== null;
    }
}