<?php

namespace App\Repository;

use App\Entity\Serie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    /**
     * Retourne un QueryBuilder pour paginer/filtrer les séries
     */
    public function getQueryForSeries(
        ?string $sort = null,
        ?string $search = null,
        array $ignoredIds = [],
        ?int $genreId = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('s');

        // Recherche texte
        if ($search !== null && trim($search) !== '') {
            $q = mb_strtolower($search, 'UTF-8');
            $qb->andWhere('LOWER(s.name) LIKE :q OR LOWER(s.overview) LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        // Filtre genre
        if ($genreId) {
            $qb->leftJoin('s.genres', 'g')
                ->andWhere('g.id = :gid')
                ->setParameter('gid', $genreId)
                ->groupBy('s.id'); // évite les doublons si plusieurs genres matchent
        }

        // Exclusions (ignorées)
        if (!empty($ignoredIds)) {
            $qb->andWhere($qb->expr()->notIn('s.id', ':ignoredIds'))
                ->setParameter('ignoredIds', $ignoredIds);
        }

        // Tri par défaut A → Z
        if ($sort === null || $sort === '') {
            $qb->orderBy('s.name', 'ASC')
                ->addOrderBy('s.popularity', 'DESC');
        }

        return $qb;
    }

    /**
     * Retourne les séries les plus récemment ajoutées
     */
    public function findRecentSeries(int $limit = 6): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.dateCreated', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les séries les plus populaires
     */
    public function findPopularSeries(int $limit = 6): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.popularity IS NOT NULL')
            ->orderBy('s.popularity', 'DESC')
            ->addOrderBy('s.vote', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }


    /**
     * Séries favorites d’un utilisateur
     */
    public function findFavorisByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'status' => 'favoris']);
    }

    /**
     * Séries à voir d’un utilisateur
     */
    public function findAVoirByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'status' => 'a-voir']);
    }

    /**
     * Séries en cours d’un utilisateur
     */
    public function findEnCoursByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'status' => 'en-cours']);
    }
}
