<?php

namespace App\Repository;

use App\Entity\Film;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Film>
 */
class FilmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Film::class);
    }

    public function findFilmsWithQueryBuilder(int $offset, int $nbPerPage, bool $count = false, ?string $sort = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('f');

        if ($search) {
            $qb->andWhere('f.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($count === true) {
            return $qb->select('count(f.id)')
                ->getQuery()
                ->getOneOrNullResult();
        }

        $allowedSorts = ['name', 'airDate', 'popularity', 'vote'];

        if ($sort) {
            switch ($sort) {
                case 'best':
                    $qb->orderBy('s.vote', 'DESC');
                    break;
                case 'fans':
                    $qb->orderBy('f.popularity', 'ASC');
                    break;
                case 'date':
                    $qb->orderBy('f.airDate', 'DESC');
                    break;
                case 'name':
                    $qb->orderBy('f.name', 'ASC');
                    break;
                default:
                    if (in_array($sort, $allowedSorts)) {
                        $qb->orderBy('f.' . $sort, 'ASC');
                    } else {
                        $qb->orderBy('f.name', 'ASC');
                    }
            }
        } else {
            $qb->orderBy('f.name', 'ASC');
        }

        return $qb->setFirstResult($offset)
            ->setMaxResults($nbPerPage)
            ->getQuery()
            ->getResult();
    }
}
