<?php

namespace App\Repository;

use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    public function findSeriesWithQueryBuilder(int $offset, int $nbPerPage, bool $count = false, ?string $sort = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($search) {
            $qb->andWhere('s.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($count === true) {
            return $qb->select('count(s.id)')->getQuery()->getOneOrNullResult();
        }

        $allowedSorts = ['name', 'firstAirDate', 'lastAirDate', 'popularity', 'vote'];

        if ($sort) {
            switch ($sort) {
                case 'best':
                    $qb->orderBy('s.vote', 'DESC'); // tri par note pour recommandations
                    break;
                case 'fans':
                    $qb->orderBy('s.popularity', 'ASC'); // tri par popularité
                    break;
                case 'date':
                    $qb->orderBy('s.firstAirDate', 'ASC');
                    break;
                case 'last':
                    $qb->orderBy('s.firstAirDate', 'DESC');
                    break;
                case 'name':
                    $qb->orderBy('s.name', 'ASC');
                    break;
                default:
                    if (in_array($sort, $allowedSorts)) {
                        $qb->orderBy('s.' . $sort, 'ASC');
                    } else {
                        $qb->orderBy('s.name', 'ASC');
                    }
            }
        } else {
            $qb->orderBy('s.name', 'ASC');
        }


        return $qb->setFirstResult($offset)
            ->setMaxResults($nbPerPage)
            ->getQuery()
            ->getResult();
    }
}
