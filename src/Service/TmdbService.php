<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TmdbService
{
    private const BASE_URI = 'https://api.themoviedb.org/3/';
    private const IMAGE_BASE = 'https://image.tmdb.org/t/p/';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Récupère les détails complets d'une série
     */
    public function getSerie(int $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . "tv/{$id}", [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'append_to_response' => 'credits,videos,watch/providers'
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('TMDb API error', ['status' => $response->getStatusCode()]);
                return null;
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('TMDb transport error: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            $this->logger->error('TMDb unexpected error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les détails d'une saison spécifique
     */
    public function getSeason(int $serieId, int $seasonNumber): ?array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                self::BASE_URI . "tv/{$serieId}/season/{$seasonNumber}",
                [
                    'query' => [
                        'api_key' => $this->apiKey,
                        'language' => 'fr-FR'
                    ],
                    'timeout' => 10
                ]
            );

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching season: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Recherche des séries
     */
    public function searchSerie(string $query, int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'search/tv', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'query' => $query,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Search error: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les séries populaires
     */
    public function getPopularSeries(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'tv/popular', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching popular series: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère l'URL complète d'une image
     */
    public function getImageUrl(?string $path, string $size = 'w500'): ?string
    {
        if (!$path) {
            return null;
        }

        return self::IMAGE_BASE . $size . $path;
    }

    /**
     * Extrait les providers de streaming pour la France
     */
    public function getStreamingProviders(array $serieData): array
    {
        if (!isset($serieData['watch/providers']['results']['FR'])) {
            return [];
        }

        $providers = [];
        $frData = $serieData['watch/providers']['results']['FR'];

        // Flatrate = abonnement (Netflix, Disney+, etc.)
        if (isset($frData['flatrate'])) {
            foreach ($frData['flatrate'] as $provider) {
                $providers[] = [
                    'name' => $provider['provider_name'],
                    'logo' => $this->getImageUrl($provider['logo_path'], 'w92'),
                    'type' => 'subscription'
                ];
            }
        }

        return $providers;
    }

    /**
     * Récupère les séries tendances de la semaine
     */
    public function getTrendingSeries(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'trending/tv/week', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching trending series: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les séries les mieux notées
     */
    public function getTopRatedSeries(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'tv/top_rated', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching top rated series: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les séries actuellement diffusées
     */
    public function getOnTheAir(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'tv/on_the_air', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching on the air series: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les séries qui arrivent bientôt
     */
    public function getAiringToday(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URI . 'tv/airing_today', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching airing today: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Découverte de séries avec filtres
     */
    public function discoverSeries(array $filters = [], int $page = 1): array
    {
        try {
            $query = array_merge([
                'api_key' => $this->apiKey,
                'language' => 'fr-FR',
                'page' => $page,
                'sort_by' => 'popularity.desc',
            ], $filters);

            $response = $this->httpClient->request('GET', self::BASE_URI . 'discover/tv', [
                'query' => $query,
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error discovering series: ' . $e->getMessage());
            return ['results' => []];
        }
    }
}