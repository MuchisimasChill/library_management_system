<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Enum\LoanStatus;
use App\Repository\LoanRepositoryInterface;
use App\Service\LoanService;
use PHPUnit\Framework\TestCase;

class LoanServiceTest extends TestCase
{
    private LoanRepositoryInterface $loanRepository;
    private LoanService $loanService;

    protected function setUp(): void
    {
        $this->loanRepository = $this->createMock(LoanRepositoryInterface::class);
        $this->loanService = new LoanService($this->loanRepository);
    }

    public function testCreateLoan(): void
    {
        // Arrange
        $book = $this->createBookEntity(1, 'Test Book', 'Test Author', '978-0-123456-78-9');
        $user = $this->createUserEntity(1, 'John', 'Doe', 'john@example.com');
        
        $this->loanRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Loan::class));

        // Act
        $result = $this->loanService->createLoan($book, $user);

        // Assert
        $this->assertInstanceOf(Loan::class, $result);
        $this->assertSame($book, $result->getBook());
        $this->assertSame($user, $result->getUser());
        $this->assertSame(LoanStatus::LENT, $result->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getLoanDate());
        $this->assertNull($result->getReturnedAt());
    }

    public function testReturnBook(): void
    {
        // Arrange
        $book = $this->createBookEntity(1, 'Test Book', 'Test Author', '978-0-123456-78-9');
        $user = $this->createUserEntity(1, 'John', 'Doe', 'john@example.com');
        
        $loan = new Loan();
        $loan->setBook($book);
        $loan->setUser($user);
        $loan->setLoanDate(new \DateTimeImmutable());
        $loan->setStatus(LoanStatus::LENT);
        
        $this->loanRepository
            ->expects($this->once())
            ->method('updateReturnData')
            ->with(
                $this->equalTo($loan),
                $this->isInstanceOf(\DateTimeImmutable::class),
                $this->equalTo(LoanStatus::RETURNED)
            );

        // Act
        $result = $this->loanService->returnBook($loan);

        // Assert
        $this->assertSame($loan, $result);
    }

    private function createBookEntity(int $id, string $title, string $author, string $isbn): Book
    {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthor($author);
        $book->setIsbn($isbn);
        $book->setPublicationYear(2024);
        $book->setNumberOfCopies(10);
        
        // Use reflection to set the ID
        $reflection = new \ReflectionClass($book);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($book, $id);
        
        return $book;
    }

    private function createUserEntity(int $id, string $name, string $surname, string $email): User
    {
        $user = new User();
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($email);
        
        // Use reflection to set the ID
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $id);
        
        return $user;
    }
}
