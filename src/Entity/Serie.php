<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\Table(
    name: 'serie',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'unique_name_date', columns: ['name', 'first_air_date'])
    ]
)]
#[UniqueEntity(fields: ['name', 'firstAirDate'], message: 'Cette série existe déjà.')]
#[ORM\HasLifecycleCallbacks]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire !')]
    #[Assert\Length(min: 1, max: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ['returning', 'ended', 'cancelled'], message: "Le choix n'est pas valable")]
    #[Assert\NotBlank(message: 'Choisissez un statut.')]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(notInRangeMessage: 'Cette valeur doit être comprise entre {{ min }} et {{ max }}.', min: 0, max: 10)]
    private ?float $vote = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'La popularité doit être positive.')]
    private ?float $popularity = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de première diffusion est requise.')]
    #[Assert\LessThan('today', message: 'La date ne doit pas être postérieure à aujourd\'hui')]
    private ?\DateTimeInterface $firstAirDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAirDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backdrop = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(nullable: true)]
    private ?int $tmdbId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $streamingLinks = [];

    #[ORM\Column]
    private ?\DateTime $dateCreated = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateModified = null;

    #[ORM\ManyToMany(targetEntity: Contributor::class, mappedBy: 'series')]
    private Collection $contributors;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'series', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'serie_genre')]
    private Collection $genres;

    #[ORM\OneToMany(targetEntity: UserSerie::class, mappedBy: 'serie')]
    private Collection $userSeries;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteSeries')]
    private Collection $favoriteSeries;

    public function __construct()
    {
        $this->contributors = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->userSeries = new ArrayCollection();
        $this->favoriteSeries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): static
    {
        $this->popularity = $popularity;
        return $this;
    }

    public function getFirstAirDate(): ?\DateTimeInterface
    {
        return $this->firstAirDate;
    }

    public function setFirstAirDate(\DateTimeInterface $firstAirDate): static
    {
        $this->firstAirDate = $firstAirDate;
        return $this;
    }

    public function getLastAirDate(): ?\DateTimeInterface
    {
        return $this->lastAirDate;
    }

    public function setLastAirDate(?\DateTimeInterface $lastAirDate): static
    {
        $this->lastAirDate = $lastAirDate;
        return $this;
    }

    public function getBackdrop(): ?string
    {
        return $this->backdrop;
    }

    public function setBackdrop(?string $backdrop): static
    {
        $this->backdrop = $backdrop;
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

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getStreamingLinks(): ?array
    {
        return $this->streamingLinks;
    }

    public function setStreamingLinks(?array $links): self
    {
        $this->streamingLinks = $links ?: [];
        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreated = new \DateTime();
        $this->dateModified = new \DateTime();
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModified = new \DateTime();
    }

    public function getContributors(): Collection
    {
        return $this->contributors;
    }

    public function addContributor(Contributor $contributor): self
    {
        if (!$this->contributors->contains($contributor)) {
            $this->contributors->add($contributor);
            $contributor->addSerie($this);
        }

        return $this;
    }

    public function removeContributor(Contributor $contributor): self
    {
        if ($this->contributors->removeElement($contributor)) {
            $contributor->removeSerie($this);
        }

        return $this;
    }

    /** @return Collection<int, Genre> */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addSerie($this);
        }
        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        if ($this->genres->removeElement($genre)) {
            $genre->removeSerie($this);
        }
        return $this;
    }

    /** @return Collection<int, UserSerie> */
    public function getUserSeries(): Collection
    {
        return $this->userSeries;
    }

    public function addUserSerie(UserSerie $userSerie): static
    {
        if (!$this->userSeries->contains($userSerie)) {
            $this->userSeries->add($userSerie);
            $userSerie->setSerie($this);
        }
        return $this;
    }

    public function removeUserSerie(UserSerie $userSerie): static
    {
        if ($this->userSeries->removeElement($userSerie)) {
            if ($userSerie->getSerie() === $this) {
                $userSerie->setSerie(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFavoriteSeries(): Collection
    {
        return $this->favoriteSeries;
    }

    public function addFavoriteSeries(User $favoriteSeries): static
    {
        if (!$this->favoriteSeries->contains($favoriteSeries)) {
            $this->favoriteSeries->add($favoriteSeries);
            $favoriteSeries->addFavoriteSeries($this);
        }

        return $this;
    }

    public function removeFavoriteSeries(User $favoriteSeries): static
    {
        if ($this->favoriteSeries->removeElement($favoriteSeries)) {
            $favoriteSeries->removeFavoriteSeries($this);
        }

        return $this;
    }
}
