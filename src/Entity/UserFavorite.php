<?php

namespace App\Entity;

use App\Repository\UserFavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFavoriteRepository::class)]
#[ORM\Table(name: 'user_tmdb_favorites')]
#[ORM\UniqueConstraint(name: 'user_tmdb_unique', columns: ['user_id', 'tmdb_id'])]
#[ORM\HasLifecycleCallbacks]
class UserFavorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer')]
    private ?int $tmdbId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $serieName = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $vote = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $year = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $addedAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    // ==================== GETTERS & SETTERS ====================

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

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getSerieName(): ?string
    {
        return $this->serieName;
    }

    public function setSerieName(string $serieName): static
    {
        $this->serieName = $serieName;
        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $this->poster = $poster;
        return $this;
    }

    public function getVote(): ?float
    {
        return $this->vote;
    }

    public function setVote(?float $vote): static
    {
        $this->vote = $vote;
        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(?string $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;
        return $this;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Retourne le temps écoulé depuis l'ajout
     */
    public function getTimeSinceAdded(): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->addedAt);

        if ($diff->y > 0) {
            return sprintf('Il y a %d an%s', $diff->y, $diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            return sprintf('Il y a %d mois', $diff->m);
        }
        if ($diff->d > 0) {
            return sprintf('Il y a %d jour%s', $diff->d, $diff->d > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            return sprintf('Il y a %d heure%s', $diff->h, $diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            return sprintf('Il y a %d minute%s', $diff->i, $diff->i > 1 ? 's' : '');
        }

        return 'À l\'instant';
    }

    /**
     * Retourne les étoiles pour la note
     */
    public function getRatingStars(): string
    {
        if (!$this->vote) {
            return '';
        }

        $rating = round($this->vote / 2); // Convertir /10 en /5
        $filled = str_repeat('★', $rating);
        $empty = str_repeat('☆', 5 - $rating);

        return $filled . $empty;
    }
}