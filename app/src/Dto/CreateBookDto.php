<?php

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

class CreateBookDto
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255, maxMessage: 'Title cannot be longer than {{ limit }} characters')]
    #[OA\Property(example: 'Harry Potter and the Philosopher\'s Stone')]
    public string $title;

    #[Assert\NotBlank(message: 'Author is required')]
    #[Assert\Length(max: 255, maxMessage: 'Author cannot be longer than {{ limit }} characters')]
    #[OA\Property(example: 'J.K. Rowling')]
    public string $author;

    #[Assert\NotBlank(message: 'ISBN is required')]
    #[Assert\Length(min: 10, max: 17, minMessage: 'ISBN must be at least 10 characters', maxMessage: 'ISBN cannot be longer than 17 characters')]
    #[OA\Property(example: '978-0-7475-3269-9')]
    public string $isbn;

    #[Assert\NotBlank(message: 'Year is required')]
    #[Assert\Positive(message: 'Year must be positive')]
    #[Assert\Range(
        min: 1000, 
        max: 2100, 
        notInRangeMessage: 'Year must be between {{ min }} and {{ max }}'
    )]
    #[OA\Property(example: 1997)]
    public int $year;

    #[Assert\NotBlank(message: 'Copies is required')]
    #[Assert\Positive(message: 'Copies must be positive')]
    #[OA\Property(example: 5)]
    public int $copies;
}
