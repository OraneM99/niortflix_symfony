<?php

namespace App\Repository;

use App\Entity\UserSerie;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSerie::class);
    }

    /**
     * Retourne les UserSerie d'un utilisateur selon le statut
     */
    public function findByUserAndStatus(User $user, string $status): array
    {
        return $this->findBy(['user' => $user, 'userStatus' => $status]);
    }
}
