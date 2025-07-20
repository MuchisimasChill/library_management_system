<?php

namespace App\Tests\Service;

use App\Dto\BookFilterDto;
use App\Entity\Book;
use App\Event\BookCreatedEvent;
use App\Repository\BookRepositoryInterface;
use App\Service\BookService;
use App\Service\CacheService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookServiceTest extends TestCase
{
    private BookRepositoryInterface|MockObject $bookRepository;
    private CacheService|MockObject $cacheService;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private BookService $bookService;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->cacheService = $this->createMock(CacheService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->bookService = new BookService($this->bookRepository, $this->cacheService, $this->eventDispatcher);
    }

    public function testGetBooks(): void
    {
        // Arrange
        $filters = new BookFilterDto(pageNumber: 1, title: 'Harry Potter');
        $books = [
            $this->createBookEntity(1, 'Harry Potter', 'J.K. Rowling', '978-0-7475-3269-9'),
            $this->createBookEntity(2, 'Harry Potter 2', 'J.K. Rowling', '978-0-7475-3269-8'),
        ];
        
        // Mock CacheService to execute callback directly
        $this->cacheService
            ->method('generateBooksListKey')
            ->willReturn('test_cache_key');
            
        $this->cacheService
            ->method('cacheBooksList')
            ->willReturnCallback(function ($key, $callback) {
                return $callback();
            });
            
        $this->bookRepository
            ->method('findByFilters')
            ->with($filters)
            ->willReturn($books);
            
        $this->bookRepository
            ->method('countByFilters')
            ->with($filters)
            ->willReturn(2);

        // Act
        $result = $this->bookService->getBooks($filters);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(4, $result); // books, totalCount, currentPage, totalPages
        $this->assertSame($books, $result['books']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertSame(1, $result['currentPage']);
        $this->assertSame(1, $result['totalPages']);
    }

    public function testGetBookById(): void
    {
        // Arrange
        $bookId = 1;
        $book = $this->createBookEntity($bookId, 'Test Book', 'Test Author', '978-0-123456-78-9');
        
        $this->cacheService
            ->method('cacheBook')
            ->willReturnCallback(function ($id, $callback) {
                return $callback();
            });
        
        $this->bookRepository
            ->method('findBookById')
            ->with($bookId)
            ->willReturn($book);

        // Act
        $result = $this->bookService->getBookById($bookId);

        // Assert
        $this->assertSame($book, $result);
    }

    public function testGetBookByIdNotFound(): void
    {
        // Arrange
        $bookId = 999;
        
        $this->cacheService
            ->method('cacheBook')
            ->willReturnCallback(function ($id, $callback) {
                return $callback();
            });
        
        $this->bookRepository
            ->method('findBookById')
            ->with($bookId)
            ->willReturn(null);

        // Act
        $result = $this->bookService->getBookById($bookId);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateBookSuccess(): void
    {
        // Arrange
        $data = [
            'title' => 'New Book',
            'author' => 'New Author',
            'isbn' => '978-0-123456-78-9',
            'year' => 2024,
            'copies' => 5
        ];
        
        $this->bookRepository
            ->method('findByFilters')
            ->willReturn([]); // No existing books with this ISBN
            
        $this->bookRepository
            ->method('save')
            ->with($this->isInstanceOf(Book::class));
            
        $this->cacheService
            ->method('invalidateBookCaches');
            
        $this->eventDispatcher
            ->method('dispatch')
            ->with($this->isInstanceOf(BookCreatedEvent::class));

        // Act
        $result = $this->bookService->createBook($data);

        // Assert
        $this->assertInstanceOf(Book::class, $result);
        $this->assertSame($data['title'], $result->getTitle());
        $this->assertSame($data['author'], $result->getAuthor());
        $this->assertSame($data['isbn'], $result->getIsbn());
        $this->assertSame($data['year'], $result->getPublicationYear());
        $this->assertSame($data['copies'], $result->getNumberOfCopies());
    }

    public function testCreateBookWithExistingIsbnThrowsException(): void
    {
        // Arrange
        $data = [
            'title' => 'New Book',
            'author' => 'New Author',
            'isbn' => '978-0-123456-78-9',
            'year' => 2024,
            'copies' => 5
        ];
        
        $existingBook = $this->createBookEntity(1, 'Existing Book', 'Existing Author', $data['isbn']);
        
        $this->bookRepository
            ->method('findByFilters')
            ->willReturn([$existingBook]); // Book with this ISBN already exists
            
        $this->bookRepository
            ->expects($this->never())
            ->method('save');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Book with this ISBN already exists');
        
        $this->bookService->createBook($data);
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
}
