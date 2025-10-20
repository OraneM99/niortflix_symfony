<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavorite::class);
    }

    /**
     * Récupère tous les favoris d'un utilisateur
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uf.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si une série est en favoris
     */
    public function isFavorite(User $user, int $tmdbId): bool
    {
        return $this->createQueryBuilder('uf')
                ->select('COUNT(uf.id)')
                ->where('uf.user = :user')
                ->andWhere('uf.tmdbId = :tmdbId')
                ->setParameter('user', $user)
                ->setParameter('tmdbId', $tmdbId)
                ->getQuery()
                ->getSingleScalarResult() > 0;
    }

    /**
     * Récupère les TMDb IDs favoris d'un utilisateur
     */
    public function getFavoriteTmdbIds(User $user): array
    {
        $results = $this->createQueryBuilder('uf')
            ->select('uf.tmdbId')
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'tmdbId');
    }
}