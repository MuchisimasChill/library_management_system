<?php

namespace App\EventListener;

use App\Event\LoanCreatedEvent;
use App\Event\LoanReturnedEvent;
use App\Event\BookCreatedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class NotificationEventListener
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    #[AsEventListener(event: LoanCreatedEvent::NAME)]
    public function onLoanCreated(LoanCreatedEvent $event): void
    {
        $loan = $event->getLoan();
        $this->notificationService->sendLoanCreatedNotification($loan);
    }

    #[AsEventListener(event: LoanReturnedEvent::NAME)]
    public function onLoanReturned(LoanReturnedEvent $event): void
    {
        $loan = $event->getLoan();
        $this->notificationService->sendLoanReturnedNotification($loan);
    }

    #[AsEventListener(event: BookCreatedEvent::NAME)]
    public function onBookCreated(BookCreatedEvent $event): void
    {
        $book = $event->getBook();
        $this->notificationService->sendBookCreatedNotification($book);
    }
}
