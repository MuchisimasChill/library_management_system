<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BookFilterDto
{
    public function __construct(
        #[Assert\Range(min: 1, minMessage: 'Page number must be at least {{ limit }}')]
        public int $pageNumber = 1,

        #[Assert\Length(max: 255, maxMessage: 'Title filter cannot be longer than {{ limit }} characters')]
        public ?string $title = null,

        #[Assert\Range(
            min: 800, 
            max: 2100, 
            notInRangeMessage: 'Year must be between {{ min }} and {{ max }}'
        )]
        public ?int $year = null,

        #[Assert\Length(max: 255, maxMessage: 'Author filter cannot be longer than {{ limit }} characters')]
        public ?string $author = null,

        #[Assert\Length(max: 17, maxMessage: 'ISBN cannot be longer than {{ limit }} characters')]
        public ?string $isbn = null,

    ) {}
}
