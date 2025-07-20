<?php

namespace App\Service;

use App\Entity\Loan;
use App\Entity\Book;
use App\Entity\User;
use App\Enum\LoanStatus;
use App\Event\LoanCreatedEvent;
use App\Event\LoanReturnedEvent;
use App\Repository\LoanRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoanService
{
    public function __construct(
        private readonly LoanRepositoryInterface $loanRepository,
        private readonly CacheService $cacheService,
        private readonly EventDispatcherInterface $eventDispatcher
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

        // Clear cache for user loans and book details
        $this->cacheService->invalidateLoanCaches($user->getId());
        $this->cacheService->invalidateBookCaches($book->getId());

        // Dispatch loan created event
        $this->eventDispatcher->dispatch(new LoanCreatedEvent($loan), LoanCreatedEvent::NAME);

        return $loan;
    }

    public function returnBook(Loan $loan): Loan
    {
        $this->loanRepository->updateReturnData(
            $loan,
            new \DateTimeImmutable(),
            LoanStatus::RETURNED
        );

        // Clear cache for user loans and book details
        $this->cacheService->invalidateLoanCaches($loan->getUser()->getId());
        $this->cacheService->invalidateBookCaches($loan->getBook()->getId());

        // Dispatch loan returned event
        $this->eventDispatcher->dispatch(new LoanReturnedEvent($loan), LoanReturnedEvent::NAME);

        return $loan;
    }
}
