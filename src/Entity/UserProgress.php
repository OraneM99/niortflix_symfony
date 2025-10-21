<?php

namespace App\Entity;

use App\Repository\UserProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProgressRepository::class)]
#[ORM\Table(name: 'user_progress')]
#[ORM\UniqueConstraint(name: 'user_episode_unique', columns: ['user_id', 'tmdb_id', 'season_number', 'episode_number'])]
#[ORM\HasLifecycleCallbacks]
class UserProgress
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

    #[ORM\Column(type: 'integer')]
    private ?int $seasonNumber = null;

    #[ORM\Column(type: 'integer')]
    private ?int $episodeNumber = null;

    #[ORM\Column(type: 'boolean')]
    private bool $watched = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $progress = null; // Progression en secondes

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $watchedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getSeasonNumber(): ?int
    {
        return $this->seasonNumber;
    }

    public function setSeasonNumber(int $seasonNumber): static
    {
        $this->seasonNumber = $seasonNumber;
        return $this;
    }

    public function getEpisodeNumber(): ?int
    {
        return $this->episodeNumber;
    }

    public function setEpisodeNumber(int $episodeNumber): static
    {
        $this->episodeNumber = $episodeNumber;
        return $this;
    }

    public function isWatched(): bool
    {
        return $this->watched;
    }

    public function setWatched(bool $watched): static
    {
        $this->watched = $watched;

        // Si marqué comme vu, enregistrer la date
        if ($watched && !$this->watchedAt) {
            $this->watchedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): static
    {
        $this->progress = $progress;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Calculer le pourcentage de progression
     */
    public function getProgressPercentage(int $duration): float
    {
        if (!$this->progress || !$duration) {
            return 0.0;
        }

        return min(100, ($this->progress / $duration) * 100);
    }

    /**
     * Vérifier si l'épisode est terminé (90% ou plus)
     */
    public function isCompleted(int $duration): bool
    {
        return $this->getProgressPercentage($duration) >= 90;
    }
}