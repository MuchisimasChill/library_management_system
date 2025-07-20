<?php

namespace App\Repository;

use App\Dto\BookFilterDto;
use App\Entity\Book;

interface BookRepositoryInterface
{
    /**
     * Get filtered list of books with pagination
     * @return Book[]
     */
    public function findByFilters(BookFilterDto $filters): array;

    /**
     * Count total books matching filters (for pagination)
     */
    public function countByFilters(BookFilterDto $filters): int;

    /**
     * Find book by ID - compatible with Doctrine signature
     */
    public function findBookById(int $id): ?Book;

    /**
     * Save book to database (create or update)
     */
    public function save(Book $book): void;
}
