<?php

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

class LoanReturnedEvent extends Event
{
    public const NAME = 'loan.returned';

    public function __construct(
        private readonly Loan $loan
    ) {}

    public function getLoan(): Loan
    {
        return $this->loan;
    }
}
