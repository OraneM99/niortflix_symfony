<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username', 'email'])]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    /**
     * @var Collection<int, UserSerie>
     */
    #[ORM\OneToMany(targetEntity: UserSerie::class, mappedBy: 'User')]
    private Collection $user;

    /**
     * @var Collection<int, UserSerie>
     */
    #[ORM\OneToMany(targetEntity: UserSerie::class, mappedBy: 'user')]
    private Collection $userSeries;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Serie>
     */
    #[ORM\ManyToMany(targetEntity: Serie::class)]
    #[ORM\JoinTable(name: "user_favorites")]
    private Collection $favoriteSeries;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->userSeries = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->isActive = true;
        $this->favoriteSeries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    /**
     * @return Collection<int, UserSerie>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(UserSerie $user): static
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->setUser($this);
        }

        return $this;
    }

    public function removeUser(UserSerie $user): static
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getUser() === $this) {
                $user->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserSerie>
     */
    public function getUserSeries(): Collection
    {
        return $this->userSeries;
    }

    public function addUserSeries(UserSerie $userSeries): static
    {
        if (!$this->userSeries->contains($userSeries)) {
            $this->userSeries->add($userSeries);
            $userSeries->setUserSeries($this);
        }

        return $this;
    }

    public function removeUserSeries(UserSerie $userSeries): static
    {
        if ($this->userSeries->removeElement($userSeries)) {
            // set the owning side to null (unless already changed)
            if ($userSeries->getUserSeries() === $this) {
                $userSeries->setUserSeries(null);
            }
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    /**
     * @return Collection<int, Serie>
     */
    public function getFavoriteSeries(): Collection
    {
        return $this->favoriteSeries;
    }

    public function addFavoriteSerie(Serie $serie): self
    {
        if (!$this->favoriteSeries->contains($serie)) {
            $this->favoriteSeries->add($serie);
        }

        return $this;
    }

    public function removeFavoriteSerie(Serie $serie): self
    {
        $this->favoriteSeries->removeElement($serie);
        return $this;
    }

    public function hasFavoriteSerie(Serie $serie): bool
    {
        return $this->favoriteSeries->contains($serie);
    }
}
