<?php

namespace App\Repository;

use App\Entity\Loan;
use App\Enum\LoanStatus;

interface LoanRepositoryInterface
{
    public function save(Loan $loan): void;

    public function updateReturnData(Loan $loan, \DateTimeImmutable $returnedAt, LoanStatus $status): void;
}
