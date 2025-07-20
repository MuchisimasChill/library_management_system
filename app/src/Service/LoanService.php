<?php

namespace App\Service;

use App\Entity\Loan;
use App\Entity\Book;
use App\Entity\User;
use App\Enum\LoanStatus;
use App\Repository\LoanRepositoryInterface;

class LoanService
{
    public function __construct(
        private readonly LoanRepositoryInterface $loanRepository
    ) {
    }

    public function createLoan(Book $book, User $user): Loan
    {
        $loan = new Loan();
        $loan->setBook($book);
        $loan->setUser($user);
        $loan->setLoanDate(new \DateTimeImmutable());
        $loan->setStatus(LoanStatus::LENT);

        $this->loanRepository->save($loan);

        return $loan;
    }

    public function returnBook(Loan $loan): Loan
    {
        $this->loanRepository->updateReturnData(
            $loan,
            new \DateTimeImmutable(),
            LoanStatus::RETURNED
        );

        return $loan;
    }
}
