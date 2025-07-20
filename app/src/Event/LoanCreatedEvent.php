<?php

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

class LoanCreatedEvent extends Event
{
    public const NAME = 'loan.created';

    public function __construct(
        private readonly Loan $loan
    ) {}

    public function getLoan(): Loan
    {
        return $this->loan;
    }
}
