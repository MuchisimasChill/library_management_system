<?php

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookDto
{
    public function __construct(
        #[OA\Property(description: 'The title of the book')]
        #[Assert\NotBlank(message: 'Title cannot be blank')]
        #[Assert\Length(max: 255, maxMessage: 'Title cannot be longer than 255 characters')]
        public string $title,
        
        #[OA\Property(description: 'The author of the book')]
        #[Assert\NotBlank(message: 'Author cannot be blank')]
        #[Assert\Length(max: 255, maxMessage: 'Author cannot be longer than 255 characters')]
        public string $author,

        #[OA\Property(description: 'The ISBN of the book')]
        #[Assert\NotBlank(message: 'ISBN cannot be blank')]
        #[Assert\Length(min: 10, max: 17, minMessage: 'ISBN cannot be shorter than 10 characters', maxMessage: 'ISBN cannot be longer than 17 characters')]
        #[Assert\Regex(
            pattern: '/^[\d\-X]+$/', 
            message: 'ISBN must contain only digits, dashes and X'
        )]
        public string $isbn,

        #[OA\Property(description: 'The publication year of the book')]
        #[Assert\Range(
            min: 800, 
            max: 2100, 
            notInRangeMessage: 'Year must be between {{ min }} and {{ max }}'
        )]
        public int $year,

        #[OA\Property(description: 'The number of copies of the book')]
        #[Assert\Range(
            min: 1, 
            minMessage: 'Number of copies must be at least {{ limit }}'
        )]
        public int $copies,
    ) {}
}
