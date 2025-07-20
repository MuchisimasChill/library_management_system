<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateLoanDto
{
    #[Assert\NotBlank(message: 'Book ID is required')]
    #[Assert\Positive(message: 'Book ID must be positive')]
    public int $bookId;

    #[Assert\NotBlank(message: 'User ID is required')]
    #[Assert\Positive(message: 'User ID must be positive')]
    public int $userId;
}
