<?php
// src/Service/TmdbService.php - VERSION BEARER TOKEN

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbService
{
    private const BASE_URI = 'https://api.themoviedb.org/3/';
    private const IMAGE_BASE = 'https://image.tmdb.org/t/p/';

    private HttpClientInterface $authenticatedClient;

    public function __construct(
        HttpClientInterface $httpClient,
        string $apiKey,
        private readonly LoggerInterface $logger
    ) {
        // Crée un client avec le Bearer Token dans les headers
        $this->authenticatedClient = $httpClient->withOptions([
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Récupère les séries populaires
     */
    public function getPopularSeries(int $page = 1): array
    {
        try {
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . 'tv/popular', [
                'query' => [
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('TMDb API error', [
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(false)
                ]);
                return ['results' => []];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching popular series: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les séries tendances
     */
    public function getTrendingSeries(int $page = 1): array
    {
        try {
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . 'trending/tv/week', [
                'query' => [
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
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . 'tv/top_rated', [
                'query' => [
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
     * Récupère les détails d'une série
     */
    public function getSerie(int $id): ?array
    {
        try {
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . "tv/{$id}", [
                'query' => [
                    'language' => 'fr-FR',
                    'append_to_response' => 'credits,videos,watch/providers'
                ],
                'timeout' => 10
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching serie: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère une saison spécifique
     */
    public function getSeason(int $serieId, int $seasonNumber): ?array
    {
        try {
            $response = $this->authenticatedClient->request(
                'GET',
                self::BASE_URI . "tv/{$serieId}/season/{$seasonNumber}",
                [
                    'query' => ['language' => 'fr-FR'],
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
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . 'search/tv', [
                'query' => [
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
     * Récupère les séries actuellement diffusées
     */
    public function getOnTheAir(int $page = 1): array
    {
        try {
            $response = $this->authenticatedClient->request('GET', self::BASE_URI . 'tv/on_the_air', [
                'query' => [
                    'language' => 'fr-FR',
                    'page' => $page
                ],
                'timeout' => 10
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching on the air: ' . $e->getMessage());
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
}