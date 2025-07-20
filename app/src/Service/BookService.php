<?php

namespace App\Service;

use App\Dto\BookFilterDto;
use App\Entity\Book;
use App\Event\BookCreatedEvent;
use App\Repository\BookRepositoryInterface;
use InvalidArgumentException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly CacheService $cacheService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Get paginated list of books with filters
     * @return array{books: Book[], totalCount: int, currentPage: int, totalPages: int}
     */
    public function getBooks(BookFilterDto $filters): array
    {
        // Generate cache key based on filters
        $cacheKey = $this->cacheService->generateBooksListKey([
            'title' => $filters->title,
            'author' => $filters->author,
            'pageNumber' => $filters->pageNumber,
        ]);

        return $this->cacheService->cacheBooksList($cacheKey, function () use ($filters) {
            $books = $this->bookRepository->findByFilters($filters);
            $totalCount = $this->bookRepository->countByFilters($filters);
            $pageSize = 10;
            $totalPages = (int) ceil($totalCount / $pageSize);

            return [
                'books' => $books,
                'totalCount' => $totalCount,
                'currentPage' => $filters->pageNumber,
                'totalPages' => $totalPages,
            ];
        });
    }

    /**
     * Get single book by ID
     */
    public function getBookById(int $id): ?Book
    {
        return $this->cacheService->cacheBook($id, function () use ($id) {
            return $this->bookRepository->findBookById($id);
        });
    }

    /**
     * Create new book (only for librarians - access control handled in controller)
     * @param array{title: string, author: string, isbn: string, year: int, copies: int} $data
     * @throws InvalidArgumentException
     */
    public function createBook(array $data): Book
    {
        // Check if book with this ISBN already exists
        $isbnFilter = new BookFilterDto(
            pageNumber: 1,
            isbn: $data['isbn']
        );

        $existingBooks = $this->bookRepository->findByFilters($isbnFilter);
        if (!empty($existingBooks)) {
            throw new InvalidArgumentException('Book with this ISBN already exists');
        }

        // Create and save entity
        $book = new Book();
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationYear((int) $data['year']);
        $book->setNumberOfCopies((int) $data['copies']);

        $this->bookRepository->save($book);

        // Clear cache after creating new book
        $this->cacheService->invalidateBookCaches();

        // Dispatch book created event
        $this->eventDispatcher->dispatch(new BookCreatedEvent($book), BookCreatedEvent::NAME);

        return $book;
    }
}
