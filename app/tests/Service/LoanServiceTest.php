<?php

namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Enum\LoanStatus;
use App\Repository\LoanRepositoryInterface;
use App\Service\LoanService;
use App\Service\CacheService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoanServiceTest extends TestCase
{
    private LoanRepositoryInterface|MockObject $loanRepository;
    private CacheService|MockObject $cacheService;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private LoanService $loanService;

    protected function setUp(): void
    {
        $this->loanRepository = $this->createMock(LoanRepositoryInterface::class);
        $this->cacheService = $this->createMock(CacheService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->loanService = new LoanService($this->loanRepository, $this->cacheService, $this->eventDispatcher);
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
            
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateLoanCaches')
            ->with($user->getId());
            
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateBookCaches')
            ->with($book->getId());
            
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

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
            
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateLoanCaches')
            ->with($user->getId());
            
        $this->cacheService
            ->expects($this->once())
            ->method('invalidateBookCaches')
            ->with($book->getId());
            
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

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
