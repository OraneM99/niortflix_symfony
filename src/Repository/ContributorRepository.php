<?php

namespace App\Repository;

use App\Entity\Contributor;
use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contributor>
 */
class ContributorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contributor::class);
    }

    public function findBySerie(Serie $serie): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.series', 's')
            ->where('s.id = :serieId')
            ->setParameter('serieId', $serie->getId())
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
