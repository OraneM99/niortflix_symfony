<?php

namespace App\Utils;

use App\Entity\Serie;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SerieExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('normalize_serie', [$this, 'normalizeSerie']),
        ];
    }

    /**
     * Normalise une série (Entity ou Array) pour l'affichage
     */
    public function normalizeSerie($serie): array
    {
        // Si c'est déjà un tableau bien formaté
        if (is_array($serie) && isset($serie['is_local'])) {
            return $serie;
        }

        // Si c'est une entité Doctrine
        if ($serie instanceof Serie) {
            return [
                'id' => $serie->getId(),
                'tmdb_id' => $serie->getTmdbId(),
                'name' => $serie->getName(),
                'overview' => $serie->getOverview(),
                'poster' => $serie->getPoster()
                    ? (filter_var($serie->getPoster(), FILTER_VALIDATE_URL)
                        ? $serie->getPoster()
                        : '/uploads/posters/series/' . $serie->getPoster())
                    : null,
                'vote' => $serie->getVote(),
                'year' => $serie->getFirstAirDate()?->format('Y'),
                'first_air_date' => $serie->getFirstAirDate()?->format('Y-m-d'),
                'is_local' => true,
            ];
        }

        // Si c'est un tableau de l'API sans is_local
        if (is_array($serie)) {
            $serie['is_local'] = $serie['is_local'] ?? false;
            return $serie;
        }

        return [];
    }
}