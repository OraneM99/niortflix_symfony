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
    public function getQueryForSeries(?string $sort = null, ?string $search = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');

        // Recherche par nom
        if ($search) {
            $qb->andWhere('s.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Tri
        switch ($sort) {
            case 'best':
                $qb->orderBy('s.vote', 'ASC');
                break;
            case 'fans':
                $qb->orderBy('s.popularity', 'ASC');
                break;
            case 'date':
                $qb->orderBy('s.firstAirDate', 'DESC');
                break;
            case 'last':
                $qb->orderBy('s.firstAirDate', 'ASC');
                break;
            case 'name':
                $qb->orderBy('s.name', 'ASC');
                break;
            default:
                $qb->orderBy('s.name', 'ASC');
                break;
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
