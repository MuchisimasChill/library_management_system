<?php

namespace App\Tests\Factory;

use App\Entity\Book;

class BookFactory
{
    public static function create(array $data = []): Book
    {
        $book = new Book();
        $book->setTitle($data['title'] ?? 'Test Book');
        $book->setAuthor($data['author'] ?? 'Test Author');
        $book->setIsbn($data['isbn'] ?? '978-0-123456-78-9');
        $book->setPublicationYear($data['publicationYear'] ?? 2024);
        $book->setNumberOfCopies($data['numberOfCopies'] ?? 10);
        
        return $book;
    }

    public static function createWithId(int $id, array $data = []): Book
    {
        $book = self::create($data);
        
        // Better approach: use a dedicated test method or mock
        return $book;
    }
}
