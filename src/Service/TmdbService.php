<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private const IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Récupère une série par son ID
     */
    public function getSerie(int $id): array
    {
        return $this->request("tv/{$id}", [
            'append_to_response' => 'credits,videos,images,recommendations'
        ]);
    }

    /**
     * Recherche de séries
     */
    public function searchSerie(string $query): array
    {
        return $this->request('search/tv', [
            'query' => $query,
            'include_adult' => false
        ]);
    }

    /**
     * Séries tendances
     */
    public function getTrendingSeries(): array
    {
        return $this->request('trending/tv/week');
    }

    /**
     * Séries populaires
     */
    public function getPopularSeries(int $page = 1): array
    {
        return $this->request('tv/popular', [
            'page' => $page
        ]);
    }

    /**
     * Meilleures séries
     */
    public function getTopRatedSeries(): array
    {
        return $this->request('tv/top_rated');
    }

    /**
     * Séries en diffusion
     */
    public function getOnTheAir(): array
    {
        return $this->request('tv/on_the_air');
    }

    /**
     * Récupère une saison
     */
    public function getSeason(int $serieId, int $seasonNumber): array
    {
        return $this->request("tv/{$serieId}/season/{$seasonNumber}");
    }

    /**
     * Récupère un épisode
     */
    public function getEpisode(int $serieId, int $seasonNumber, int $episodeNumber): array
    {
        return $this->request("tv/{$serieId}/season/{$seasonNumber}/episode/{$episodeNumber}");
    }

    /**
     * Génère l'URL complète d'une image
     */
    public function getImageUrl(?string $path, string $size = 'w500'): ?string
    {
        if (!$path) {
            return null;
        }

        return self::IMAGE_BASE_URL . $size . $path;
    }

    /**
     * Requête générique à l'API TMDb
     */
    private function request(string $endpoint, array $params = []): array
    {
        try {
            $params['api_key'] = $this->apiKey;
            $params['language'] = 'fr-FR';

            $url = self::BASE_URL . '/' . $endpoint;

            $response = $this->httpClient->request('GET', $url, [
                'query' => $params,
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            $this->logger->error('Erreur API TMDb: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'params' => $params
            ]);

            return [];
        }
    }
}