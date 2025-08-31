<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Anime>
 */

class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    public function findAnimesWithQueryBuilder(int $offset, int $nbPerPage, bool $count = false, ?string $sort = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($search) {
            $qb->andWhere('a.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($count === true) {
            return $qb->select('count(a.id)')->getQuery()->getOneOrNullResult();
        }

        $allowedSorts = ['name', 'firstAirDate', 'lastAirDate', 'popularity', 'vote'];

        if ($sort && in_array($sort, $allowedSorts)) {
            $qb->orderBy('a.' . $sort, 'ASC');
        } else {
            $qb->orderBy('a.name', 'ASC');
        }

        return $qb->setFirstResult($offset)
            ->setMaxResults($nbPerPage)
            ->getQuery()
            ->getResult();
    }
}
