<?php

namespace App\Service;

use App\Entity\Serie;
use App\Entity\User;
use App\Repository\SerieRepository;

class SerieService
{
    public function __construct(private SerieRepository $repo) {}

    /**
     * Retourne une série aléatoire.
     * Si l’utilisateur a des favoris, on évite de renvoyer une série déjà favorite.
     */
    public function getRandomSerie(?User $user = null): ?Serie
    {
        $qb = $this->repo->createQueryBuilder('s');

        if ($user && !$user->getFavoriteSeries()->isEmpty()) {
            $favIds = $user->getFavoriteSeries()->map(fn(Serie $s) => $s->getId())->toArray();
            $qb->andWhere('s.id NOT IN (:favIds)')
                ->setParameter('favIds', $favIds);
        }

        $series = $qb->getQuery()->getResult();
        if (empty($series)) {
            return null;
        }

        return $series[array_rand($series)];
    }
}