<?php

namespace App\Entity;

use App\Enum\UserType;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'User ID', example: 1),
        new OA\Property(property: 'name', type: 'string', description: 'First name', example: 'John'),
        new OA\Property(property: 'surname', type: 'string', description: 'Last name', example: 'Doe'),
        new OA\Property(property: 'email', type: 'string', description: 'Email address', example: 'john.doe@example.com'),
        new OA\Property(property: 'type', type: 'string', description: 'User type', enum: ['LIBRARIAN', 'MEMBER'], example: 'MEMBER')
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'user:list', 'loan:read', 'loan:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list'])]
    private ?string $surname = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'user:list'])]
    private ?string $email = null;

    #[ORM\Column(enumType: UserType::class)]
    #[Groups(['user:read', 'user:list'])]
    private ?UserType $type = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['user:read'])]
    private Collection $loans;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

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

    public function getType(): ?UserType
    {
        return $this->type;
    }

    public function setType(UserType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setUser($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getUser() === $this) {
                $loan->setUser(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        return match ($this->type?->value) {
            UserType::LIBRARIAN->value => ['ROLE_LIBRARIAN'],
            UserType::MEMBER->value => ['ROLE_MEMBER'],
            default => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void
    {
        // No temporary sensitive data stored
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }
}
