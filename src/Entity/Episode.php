<?php

namespace App\Entity;

use App\Repository\EpisodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EpisodeRepository::class)]
#[ORM\Table(name: 'episode')]
class Episode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Le numéro d\'épisode est obligatoire')]
    #[Assert\Positive(message: 'Le numéro doit être positif')]
    private ?int $episodeNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'épisode est obligatoire')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $airDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $runtime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stillPath = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tmdbId = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Range(min: 0, max: 10)]
    private ?float $vote = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'episodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Season $season = null;

    #[ORM\OneToMany(targetEntity: UserEpisode::class, mappedBy: 'episode', cascade: ['remove'], orphanRemoval: true)]
    private Collection $userEpisodes;

    public function __construct()
    {
        $this->userEpisodes = new ArrayCollection();
    }

    // ==================== GETTERS & SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): static
    {
        $this->overview = $overview;
        return $this;
    }

    public function getAirDate(): ?\DateTimeInterface
    {
        return $this->airDate;
    }

    public function setAirDate(?\DateTimeInterface $airDate): static
    {
        $this->airDate = $airDate;
        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): static
    {
        $this->runtime = $runtime;
        return $this;
    }

    public function getStillPath(): ?string
    {
        return $this->stillPath;
    }

    public function setStillPath(?string $stillPath): static
    {
        $this->stillPath = $stillPath;
        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;
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

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;
        return $this;
    }

    /**
     * @return Collection<int, UserEpisode>
     */
    public function getUserEpisodes(): Collection
    {
        return $this->userEpisodes;
    }

    public function addUserEpisode(UserEpisode $userEpisode): static
    {
        if (!$this->userEpisodes->contains($userEpisode)) {
            $this->userEpisodes->add($userEpisode);
            $userEpisode->setEpisode($this);
        }

        return $this;
    }

    public function removeUserEpisode(UserEpisode $userEpisode): static
    {
        if ($this->userEpisodes->removeElement($userEpisode)) {
            if ($userEpisode->getEpisode() === $this) {
                $userEpisode->setEpisode(null);
            }
        }

        return $this;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Retourne le nom formaté "S01E01 - Titre"
     */
    public function getFormattedName(): string
    {
        $seasonNum = $this->season?->getSeasonNumber() ?? 0;
        return sprintf(
            'S%02dE%02d - %s',
            $seasonNum,
            $this->episodeNumber,
            $this->name
        );
    }

    /**
     * Vérifie si l'épisode est déjà diffusé
     */
    public function isAired(): bool
    {
        if (!$this->airDate) {
            return false;
        }

        return $this->airDate <= new \DateTime();
    }

    /**
     * Retourne la durée formatée
     */
    public function getFormattedRuntime(): ?string
    {
        if (!$this->runtime) {
            return null;
        }

        $hours = floor($this->runtime / 60);
        $minutes = $this->runtime % 60;

        if ($hours > 0) {
            return sprintf('%dh%02d', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Pour l'affichage dans les formulaires
     */
    public function __toString(): string
    {
        return $this->getFormattedName();
    }
}