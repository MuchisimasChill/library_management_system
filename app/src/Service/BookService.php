<?php

namespace App\Service;

use App\Dto\BookFilterDto;
use App\Entity\Book;
use App\Repository\BookRepositoryInterface;
use InvalidArgumentException;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository
    ) {}

    /**
     * Get paginated list of books with filters
     * @return array{books: Book[], totalCount: int, currentPage: int, totalPages: int}
     */
    public function getBooks(BookFilterDto $filters): array
    {
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
    }

    /**
     * Get single book by ID
     */
    public function getBookById(int $id): ?Book
    {
        return $this->bookRepository->findBookById($id);
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

        return $book;
    }
}
