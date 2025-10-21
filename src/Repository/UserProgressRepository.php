<?php

namespace App\Repository;

use App\Entity\UserProgress;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProgress::class);
    }

    /**
     * Récupérer la progression d'un utilisateur pour une série
     */
    public function findByUserAndSerie(User $user, int $tmdbId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.tmdbId = :tmdbId')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->orderBy('p.seasonNumber', 'ASC')
            ->addOrderBy('p.episodeNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer la progression pour une saison spécifique
     */
    public function findByUserAndSeason(User $user, int $tmdbId, int $seasonNumber): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.tmdbId = :tmdbId')
            ->andWhere('p.seasonNumber = :seasonNumber')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->setParameter('seasonNumber', $seasonNumber)
            ->orderBy('p.episodeNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer un épisode spécifique
     */
    public function findEpisode(User $user, int $tmdbId, int $seasonNumber, int $episodeNumber): ?UserProgress
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.tmdbId = :tmdbId')
            ->andWhere('p.seasonNumber = :seasonNumber')
            ->andWhere('p.episodeNumber = :episodeNumber')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->setParameter('seasonNumber', $seasonNumber)
            ->setParameter('episodeNumber', $episodeNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compter les épisodes vus pour une série
     */
    public function countWatchedEpisodes(User $user, int $tmdbId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user = :user')
            ->andWhere('p.tmdbId = :tmdbId')
            ->andWhere('p.watched = true')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupérer le prochain épisode à regarder
     */
    public function findNextEpisodeToWatch(User $user, int $tmdbId): ?array
    {
        // Trouver le dernier épisode vu
        $lastWatched = $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.tmdbId = :tmdbId')
            ->andWhere('p.watched = true')
            ->setParameter('user', $user)
            ->setParameter('tmdbId', $tmdbId)
            ->orderBy('p.seasonNumber', 'DESC')
            ->addOrderBy('p.episodeNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastWatched) {
            return [
                'season' => $lastWatched->getSeasonNumber(),
                'episode' => $lastWatched->getEpisodeNumber() + 1
            ];
        }

        // Sinon, retourner S01E01
        return ['season' => 1, 'episode' => 1];
    }
}