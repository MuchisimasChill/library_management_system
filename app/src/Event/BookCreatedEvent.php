<?php

namespace App\Event;

use App\Entity\Book;
use Symfony\Contracts\EventDispatcher\Event;

class BookCreatedEvent extends Event
{
    public const NAME = 'book.created';

    public function __construct(
        private readonly Book $book
    ) {}

    public function getBook(): Book
    {
        return $this->book;
    }
}
