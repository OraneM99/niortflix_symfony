<?php

namespace App\Entity;

use App\Repository\UserEpisodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserEpisodeRepository::class)]
#[ORM\Table(name: 'user_episode')]
#[ORM\UniqueConstraint(name: 'user_episode_unique', columns: ['user_id', 'episode_id'])]
#[ORM\HasLifecycleCallbacks]
class UserEpisode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Episode::class, inversedBy: 'userEpisodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Episode $episode = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $watched = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $watchedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $rating = null;

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

    public function getEpisode(): ?Episode
    {
        return $this->episode;
    }

    public function setEpisode(?Episode $episode): static
    {
        $this->episode = $episode;
        return $this;
    }

    public function isWatched(): bool
    {
        return $this->watched;
    }

    public function setWatched(bool $watched): static
    {
        $this->watched = $watched;

        // Met à jour automatiquement la date de visionnage
        if ($watched && !$this->watchedAt) {
            $this->watchedAt = new \DateTimeImmutable();
        } elseif (!$watched) {
            $this->watchedAt = null;
        }

        return $this;
    }

    public function getWatchedAt(): ?\DateTimeImmutable
    {
        return $this->watchedAt;
    }

    public function setWatchedAt(?\DateTimeImmutable $watchedAt): static
    {
        $this->watchedAt = $watchedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        // Validation : note entre 1 et 5
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new \InvalidArgumentException('La note doit être entre 1 et 5');
        }

        $this->rating = $rating;
        return $this;
    }

    // ==================== LIFECYCLE CALLBACKS ====================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Marque l'épisode comme vu maintenant
     */
    public function markAsWatched(): static
    {
        $this->watched = true;
        $this->watchedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Marque l'épisode comme non vu
     */
    public function markAsUnwatched(): static
    {
        $this->watched = false;
        $this->watchedAt = null;
        return $this;
    }

    /**
     * Toggle l'état de visionnage
     */
    public function toggleWatched(): static
    {
        if ($this->watched) {
            $this->markAsUnwatched();
        } else {
            $this->markAsWatched();
        }
        return $this;
    }

    /**
     * Retourne le temps écoulé depuis le visionnage
     * Ex: "Il y a 2 jours"
     */
    public function getTimeSinceWatched(): ?string
    {
        if (!$this->watchedAt) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->watchedAt);

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
     * Ex: "★★★☆☆"
     */
    public function getRatingStars(): string
    {
        if (!$this->rating) {
            return '';
        }

        $filled = str_repeat('★', $this->rating);
        $empty = str_repeat('☆', 5 - $this->rating);

        return $filled . $empty;
    }

    /**
     * Vérifie si l'épisode a été vu récemment (< 7 jours)
     */
    public function isRecentlyWatched(): bool
    {
        if (!$this->watchedAt) {
            return false;
        }

        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        return $this->watchedAt >= $sevenDaysAgo;
    }
}