<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[OA\Schema(
    schema: 'Book',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Book ID', example: 1),
        new OA\Property(property: 'title', type: 'string', description: 'Book title', example: 'Harry Potter and the Philosopher\'s Stone'),
        new OA\Property(property: 'author', type: 'string', description: 'Author name', example: 'J.K. Rowling'),
        new OA\Property(property: 'isbn', type: 'string', description: 'ISBN number', example: '978-0-7475-3269-9'),
        new OA\Property(property: 'publicationYear', type: 'integer', description: 'Publication year', example: 1997),
        new OA\Property(property: 'numberOfCopies', type: 'integer', description: 'Number of copies', example: 10)
    ]
)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['book:read', 'book:list', 'loan:read', 'loan:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['book:read', 'book:list', 'loan:read', 'loan:list'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['book:read', 'book:list', 'loan:read', 'loan:list'])]
    private ?string $author = null;

    #[ORM\Column(length: 17, unique: true)]
    #[Groups(['book:read', 'book:list'])]
    private ?string $isbn = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true])]
    #[Groups(['book:read', 'book:list'])]
    private ?int $publicationYear = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true])]
    #[Groups(['book:read', 'book:list'])]
    private ?int $numberOfCopies = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'book')]
    private Collection $loans;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getPublicationYear(): ?int
    {
        return $this->publicationYear;
    }

    public function setPublicationYear(int $publicationYear): static
    {
        $this->publicationYear = $publicationYear;

        return $this;
    }

    public function getNumberOfCopies(): ?int
    {
        return $this->numberOfCopies;
    }

    public function setNumberOfCopies(int $numberOfCopies): static
    {
        $this->numberOfCopies = $numberOfCopies;

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
            $loan->setBook($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            // set the owning side to null (unless already changed)
            if ($loan->getBook() === $this) {
                $loan->setBook(null);
            }
        }

        return $this;
    }
}
