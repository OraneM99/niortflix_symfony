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

    public function findSeriesWithQueryBuilder(int $offset, int $nbPerPage, bool $count = false): array
    {

        $q = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status OR s.firstAirDate >= :date')
            ->setParameter('status', 'returning')
            ->setParameter('date', new \DateTime('1998-01-01'));

        if ($count === false) {
            return $q->orderBy('s.name', 'ASC')
                ->setFirstResult($offset)
                ->setMaxResults($nbPerPage)
                ->getQuery()
                ->getResult();
        }

        return $q->select('count(s.id)')->getQuery()->getOneOrNullResult();

    }

    public function findSeriesWithDQL(int $offset, int $nbPerPage, string $genre): array
    {
        $dql = <<<DQL
            SELECT s FROM App\Entity\Serie s
            WHERE (s.status = :status OR s.firstAirDate >= :date)
            AND s.genres like :genre
            ORDER BY s.name ASC
DQL;

        return $this->getEntityManager()->createQuery($dql)
            ->setParameter('status', 'returning')
            ->setParameter('date', new \DateTime('1998-01-01'))
            ->setParameter('genre', "%$genre%")
            ->setMaxResults($nbPerPage)
            ->setFirstResult($offset)
            ->execute();
    }

    public function getSeriesWithRawSQL(int $offset, int $nbPerPage): array
    {
        $sql = <<<SQL
        SELECT * FROM serie WHERE genre like :genre
        ORDER BY name ASC
SQL;

        $connection = $this->getEntityManager()->getConnection();
        return $connection->prepare($sql)
            ->executeQuery([
                'nbPerPage' => $nbPerPage,
                'offset' => $offset,
                'genre' => '%Drama%'
            ])
            ->fetchAllAssociative();
    }

    //    /**
    //     * @return Serie[] Returns an array of Serie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Serie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
