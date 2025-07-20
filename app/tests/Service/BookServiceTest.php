<?php

namespace App\Tests\Service;

use App\Dto\BookFilterDto;
use App\Entity\Book;
use App\Repository\BookRepositoryInterface;
use App\Service\BookService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BookServiceTest extends TestCase
{
    private BookRepositoryInterface $bookRepository;
    private BookService $bookService;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepositoryInterface::class);
        $this->bookService = new BookService($this->bookRepository);
    }

    public function testGetBooks(): void
    {
        // Arrange
        $filters = new BookFilterDto(pageNumber: 1, title: 'Harry Potter');
        $books = [
            $this->createBookEntity(1, 'Harry Potter', 'J.K. Rowling', '978-0-7475-3269-9'),
            $this->createBookEntity(2, 'Harry Potter 2', 'J.K. Rowling', '978-0-7475-3269-8'),
        ];
        
        $this->bookRepository
            ->expects($this->once())
            ->method('findByFilters')
            ->with($filters)
            ->willReturn($books);
            
        $this->bookRepository
            ->expects($this->once())
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
        
        $this->bookRepository
            ->expects($this->once())
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
        
        $this->bookRepository
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('findByFilters')
            ->willReturn([]); // No existing books with this ISBN
            
        $this->bookRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Book::class));

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
            ->expects($this->once())
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
