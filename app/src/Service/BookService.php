<?php

namespace App\Service;

use App\Dto\BookDto;
use App\Dto\BookFilterDto;
use App\Entity\Book;
use App\Repository\BookRepositoryInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly ValidatorInterface $validator
    ) {}

    /**
     * Get paginated list of books with filters
     * @return array{books: BookDto[], totalCount: int, currentPage: int, totalPages: int}
     */
    public function getBooks(BookFilterDto $filters): array
    {
        $books = $this->bookRepository->findByFilters($filters);
        $totalCount = $this->bookRepository->countByFilters($filters);
        $pageSize = 10;
        $totalPages = (int) ceil($totalCount / $pageSize);

        $bookDtos = array_map(
            fn(Book $book) => $this->mapBookToDto($book),
            $books
        );

        return [
            'books' => $bookDtos,
            'totalCount' => $totalCount,
            'currentPage' => $filters->pageNumber,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Get single book by ID
     */
    public function getBookById(int $id): ?BookDto
    {
        $book = $this->bookRepository->findBookById($id);
        
        return $book ? $this->mapBookToDto($book) : null;
    }

    /**
     * Create new book (only for librarians - access control handled in controller)
     * @param array{title: string, author: string, isbn: string, year: int, copies: int} $data
     * @throws ValidationFailedException
     */
    public function createBook(array $data): BookDto
    {
        $bookDto = new BookDto(
            title: $data['title'],
            author: $data['author'],
            isbn: $data['isbn'],
            year: (int) $data['year'],
            copies: (int) $data['copies']
        );

        $errors = $this->validator->validate($bookDto);
        if (count($errors) > 0) {
            throw new ValidationFailedException($bookDto, $errors);
        }

        $isbnFilter = new BookFilterDto(
            pageNumber: 1,
            isbn: $data['isbn']
        );

        $existingBooks = $this->bookRepository->findByFilters($isbnFilter);
        if (!empty($existingBooks)) {
            throw new ValidationFailedException($bookDto, 
                new ConstraintViolationList([
                    new ConstraintViolation('Book with this ISBN already exists', null, [], $bookDto, 'isbn', $data['isbn'])
                ])
            );
        }

        // Create and save entity
        $book = new Book();
        $book->setTitle($data['title']);
        $book->setAuthor($data['author']);
        $book->setIsbn($data['isbn']);
        $book->setPublicationYear((int) $data['year']);
        $book->setNumberOfCopies((int) $data['copies']);

        $this->bookRepository->save($book);

        return $this->mapBookToDto($book);
    }

    /**
     * Map Book entity to BookDto
     */
    private function mapBookToDto(Book $book): BookDto
    {
        return new BookDto(
            title: $book->getTitle(),
            author: $book->getAuthor(),
            isbn: $book->getIsbn(),
            year: $book->getPublicationYear(),
            copies: $book->getNumberOfCopies()
        );
    }
}
