<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class SerieManagerService
{
    public function __construct(
        private readonly TmdbService $tmdbService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Récupère les séries tendances
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
     * Récupère les séries les mieux notées
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
     * Récupère les séries populaires
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
     * Récupère les nouvelles sorties
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
     * Recherche de séries
     */
    public function searchSeries(string $query, int $limit = 20): array
    {
        try {
            $data = $this->tmdbService->searchSerie($query);
            return [
                'api' => $this->formatSeriesFromTmdb($data['results'] ?? [], $limit)
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur recherche API: ' . $e->getMessage());
            return ['api' => []];
        }
    }

    /**
     * Récupère les détails d'une série
     */
    public function getSerieDetails(int $tmdbId): ?array
    {
        try {
            $data = $this->tmdbService->getSerie($tmdbId);
            return $this->formatSingleSerieFromTmdb($data);
        } catch (\Exception $e) {
            $this->logger->error('Erreur détails série: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Formate les données TMDb
     */
    private function formatSeriesFromTmdb(array $series, int $limit): array
    {
        $formatted = [];
        $count = 0;

        foreach ($series as $serie) {
            if ($count >= $limit || empty($serie['id'])) {
                continue;
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
                'genre_ids' => $serie['genre_ids'] ?? [],
            ];

            $count++;
        }

        return $formatted;
    }

    /**
     * Formate une série complète
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
        ];
    }
}