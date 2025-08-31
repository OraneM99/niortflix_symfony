<?php

namespace App\Entity;

use App\Repository\AnimeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnimeRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'first_air_date'])]
#[UniqueEntity(fields: ['name', 'firstAirDate'], message: 'Cet anime existe déjà.')]
class Anime
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire !')]
    #[Assert\Length(min: 3, max: 150,
        minMessage: 'Un nom doit comporter au moins {{ limit }} caractères.',
        maxMessage: 'Un nom ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $overview = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(choices: ['returning', 'ended', 'cancelled'],
        message: "Le choix n'est pas valable")]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        notInRangeMessage: 'Cette valeur doit être comprise entre {{ min }} et {{ max }}.',
        min: 0, max: 10)]
    private ?float $vote = null;

    #[ORM\Column(nullable: true)]
    private ?int $popularity = null;

    #[ORM\Column(nullable: true)]
    #[Assert\LessThan('today',
        message: 'La date ne doit pas être postérieure à {{ compared_value }}')]
    private ?\DateTime $firstAirDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'firstAirDate')]
    #[Assert\When(
        expression: "this.getStatus() == 'returning'",
        constraints: [
            new Assert\Blank(message: 'Au vu du statut, il ne faut pas de date de fin.')
        ]
    )]
    #[Assert\When(
        expression: "this.getStatus() == 'ended' || this.getStatus() == 'cancelled'",
        constraints: [
            new Assert\NotBlank(message: 'Au vu du statut, il faut une date de fin.')
        ]
    )]
    private ?\DateTime $lastAirDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backdrop = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(nullable: true)]
    private ?int $tmdb_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $streamingLink = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateCreated = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?\DateTime $dateModified = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $genres = [];

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

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function setStatus(?string $status): static
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

    public function getPopularity(): ?int
    {
        return $this->popularity;
    }

    public function setPopularity(?int $popularity): static
    {
        $this->popularity = $popularity;

        return $this;
    }

    public function getFirstAirDate(): ?\DateTime
    {
        return $this->firstAirDate;
    }

    public function setFirstAirDate(?\DateTime $firstAirDate): static
    {
        $this->firstAirDate = $firstAirDate;

        return $this;
    }

    public function getLastAirDate(): ?\DateTime
    {
        return $this->lastAirDate;
    }

    public function setLastAirDate(?\DateTime $lastAirDate): static
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
        return $this->tmdb_id;
    }

    public function setTmdbId(?int $tmdb_id): static
    {
        $this->tmdb_id = $tmdb_id;

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

    public function getStreamingLink(): ?string
    {
        return $this->streamingLink;
    }

    public function setStreamingLink(?string $streamingLink): static
    {
        $this->streamingLink = $streamingLink;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->dateCreated = new \DateTime();
        $this->dateModified = new \DateTime();
    }

    public function getDateCreated(): ?\DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(?\DateTime $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): \DateTime
    {
        return $this->dateModified;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModified = new \DateTime();
    }

    public function setDateModified(\DateTime $dateModified): static
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(array $genres): static
    {
        $this->genres = $genres;

        return $this;
    }
}
