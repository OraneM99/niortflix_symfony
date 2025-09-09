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
        array $ignoredIds = []
    ): QueryBuilder {
        // alias 's' pour correspondre aux champs passés à knp_pagination_sortable
        $qb = $this->createQueryBuilder('s');

        // Recherche plein texte simple sur nom + synopsis
        if (null !== $search && '' !== trim($search)) {
            $q = mb_strtolower($search, 'UTF-8');
            $qb->andWhere('LOWER(s.name) LIKE :q OR LOWER(s.overview) LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        // Exclure des séries (ex: "ignorées" stockées en session)
        if (!empty($ignoredIds)) {
            $qb->andWhere($qb->expr()->notIn('s.id', ':ignoredIds'))
                ->setParameter('ignoredIds', $ignoredIds);
        }

        // Tri par défaut si aucun tri Knp n'est demandé
        if (null === $sort || '' === $sort) {
            $qb->orderBy('s.popularity', 'DESC')
                ->addOrderBy('s.vote', 'DESC')
                ->addOrderBy('s.name', 'ASC');
        }

        return $qb;
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
