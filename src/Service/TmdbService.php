<?php

namespace App\Service;

use GuzzleHttp\Client;

class TmdbService
{
    private Client $client;

    public function __construct(private readonly string $apiKey)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.themoviedb.org/3/',
        ]);
    }

    // Récupérer les détails d'une série par ID TMDb
    public function getSerie(int $id): array
    {
        $response = $this->client->request('GET', "tv/{$id}", [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => 'fr-FR',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // Rechercher une série par nom
    public function searchSerie(string $query): array
    {
        $response = $this->client->request('GET', "search/tv", [
            'query' => [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => 'fr-FR',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // Récupérer les détails d'un film par ID TMDb
    public function getMovie(int $id): array
    {
        $response = $this->client->request('GET', "movie/{$id}", [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => 'fr-FR',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // Rechercher un film par nom
    public function searchMovie(string $query): array
    {
        $response = $this->client->request('GET', "search/movie", [
            'query' => [
                'api_key' => $this->apiKey,
                'query' => $query,
                'language' => 'fr-FR',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}