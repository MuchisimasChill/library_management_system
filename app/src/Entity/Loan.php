<?php

namespace App\Entity;

use App\Enum\LoanStatus;
use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[OA\Schema(
    schema: 'Loan',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Loan ID', example: 1),
        new OA\Property(
            property: 'book',
            type: 'object',
            description: 'Book details',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'title', type: 'string', example: 'Harry Potter'),
                new OA\Property(property: 'author', type: 'string', example: 'J.K. Rowling')
            ]
        ),
        new OA\Property(
            property: 'user',
            type: 'object', 
            description: 'User details',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'John'),
                new OA\Property(property: 'surname', type: 'string', example: 'Doe')
            ]
        ),
        new OA\Property(property: 'loanDate', type: 'string', format: 'date-time', description: 'Loan date', example: '2024-07-20T14:30:00+00:00'),
        new OA\Property(property: 'returnedAt', type: 'string', format: 'date-time', description: 'Return date', example: '2024-08-20T16:45:00+00:00', nullable: true),
        new OA\Property(property: 'status', type: 'string', description: 'Loan status', enum: ['lent', 'returned', 'overdue', 'lost'], example: 'lent')
    ]
)]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['loan:read', 'loan:list', 'user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read', 'loan:list'])]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read', 'loan:list'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['loan:read', 'loan:list', 'user:read'])]
    private ?\DateTimeImmutable $loanDate = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['loan:read', 'loan:list', 'user:read'])]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(enumType: LoanStatus::class)]
    #[Groups(['loan:read', 'loan:list', 'user:read'])]
    private ?LoanStatus $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
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

    public function getLoanDate(): ?\DateTimeImmutable
    {
        return $this->loanDate;
    }

    public function setLoanDate(\DateTimeImmutable $loanDate): static
    {
        $this->loanDate = $loanDate;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }

    public function getStatus(): ?LoanStatus
    {
        return $this->status;
    }

    public function setStatus(LoanStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}
