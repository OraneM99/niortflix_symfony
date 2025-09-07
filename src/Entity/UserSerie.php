<?php

namespace App\Entity;

use App\Repository\UserSerieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSerieRepository::class)]
class UserSerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Relation vers l'utilisateur
    #[ORM\ManyToOne(inversedBy: 'userSeries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Relation vers la série
    #[ORM\ManyToOne(inversedBy: 'userSeries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Serie $serie = null;

    // Statut de l'utilisateur pour cette série : favoris, a-voir, en-cours
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $userStatus = null;

    // Progression de visionnage
    #[ORM\Column(nullable: true)]
    private ?int $progression = null;

    // -----------------------
    // Getters & Setters
    // -----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;
        return $this;
    }

    public function getUserStatus(): ?string
    {
        return $this->userStatus;
    }

    public function setUserStatus(?string $userStatus): static
    {
        $this->userStatus = $userStatus;
        return $this;
    }

    public function getProgression(): ?int
    {
        return $this->progression;
    }

    public function setProgression(?int $progression): static
    {
        $this->progression = $progression;
        return $this;
    }
}
