<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserEpisode;
use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserEpisodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEpisode::class);
    }

    /**
     * Compte les épisodes vus d'une série par un utilisateur
     */
    public function countWatchedEpisodes(User $user, Serie $serie): int
    {
        return $this->createQueryBuilder('ue')
            ->select('COUNT(ue.id)')
            ->join('ue.episode', 'e')
            ->join('e.season', 's')
            ->where('ue.user = :user')
            ->andWhere('s.serie = :serie')
            ->andWhere('ue.watched = true')
            ->setParameter('user', $user)
            ->setParameter('serie', $serie)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les épisodes vus d'une série par un utilisateur
     */
    public function findWatchedBySerieAndUser(Serie $serie, User $user): array
    {
        return $this->createQueryBuilder('ue')
            ->join('ue.episode', 'e')
            ->join('e.season', 's')
            ->where('ue.user = :user')
            ->andWhere('s.serie = :serie')
            ->andWhere('ue.watched = true')
            ->setParameter('user', $user)
            ->setParameter('serie', $serie)
            ->orderBy('s.seasonNumber', 'ASC')
            ->addOrderBy('e.episodeNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}