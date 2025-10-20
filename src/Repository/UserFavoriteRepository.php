<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFavorite>
 */
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
     * Vérifie si une série est en favoris pour un utilisateur
     */
    public function isFavorite(User $user, int $tmdbId): bool
    {
        $count = $this->createQueryBuilder('uf')
            ->select('COUNT(uf.id)')
            ->where('uf.user = :user')
            ->andWhere('uf.tmdbId = :tmdbId')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Récupère un favori spécifique
     */
    public function findOneByUserAndTmdbId(User $user, int $tmdbId): ?UserFavorite
    {
        return $this->findOneBy([
            'user' => $user,
            'tmdbId' => $tmdbId
        ]);
    }

    /**
     * Récupère les TMDb IDs favoris d'un utilisateur (pour filtrage)
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

    /**
     * Compte le nombre de favoris d'un utilisateur
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('uf')
            ->select('COUNT(uf.id)')
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les derniers favoris ajoutés par un utilisateur
     */
    public function findRecentByUser(User $user, int $limit = 6): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uf.addedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les favoris d'un utilisateur avec pagination
     */
    public function findByUserPaginated(User $user, int $page = 1, int $limit = 12): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('uf')
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uf.addedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les séries favorites les mieux notées d'un utilisateur
     */
    public function findTopRatedByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.user = :user')
            ->andWhere('uf.vote IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('uf.vote', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les favoris d'un utilisateur
     */
    public function searchByUser(User $user, string $query): array
    {
        return $this->createQueryBuilder('uf')
            ->where('uf.user = :user')
            ->andWhere('LOWER(uf.serieName) LIKE :query')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . strtolower($query) . '%')
            ->orderBy('uf.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime tous les favoris d'un utilisateur
     */
    public function deleteAllByUser(User $user): int
    {
        return $this->createQueryBuilder('uf')
            ->delete()
            ->where('uf.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les statistiques de favoris par année
     */
    public function getFavoritesByYear(User $user): array
    {
        return $this->createQueryBuilder('uf')
            ->select('uf.year, COUNT(uf.id) as count')
            ->where('uf.user = :user')
            ->andWhere('uf.year IS NOT NULL')
            ->setParameter('user', $user)
            ->groupBy('uf.year')
            ->orderBy('uf.year', 'DESC')
            ->getQuery()
            ->getResult();
    }
}