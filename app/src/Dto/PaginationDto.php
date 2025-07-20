<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PaginationDto
{
    #[Assert\Positive(message: 'Page must be positive')]
    #[Assert\Type(type: 'integer', message: 'Page must be an integer')]
    public int $page = 1;

    #[Assert\Positive(message: 'Limit must be positive')]
    #[Assert\Range(
        min: 1, 
        max: 100, 
        notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}'
    )]
    #[Assert\Type(type: 'integer', message: 'Limit must be an integer')]
    public int $limit = 10;
}
