<?php

namespace App\Tests\EventListener;

use App\Event\LoanCreatedEvent;
use App\Event\LoanReturnedEvent;
use App\Event\BookCreatedEvent;
use App\EventListener\NotificationEventListener;
use App\Service\NotificationService;
use App\Entity\Loan;
use App\Entity\Book;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationEventListenerTest extends TestCase
{
    private NotificationService $notificationService;
    private NotificationEventListener $eventListener;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->eventListener = new NotificationEventListener($this->notificationService);
    }

    public function testOnLoanCreated(): void
    {
        $loan = $this->createMock(Loan::class);
        $event = new LoanCreatedEvent($loan);

        $this->notificationService
            ->expects($this->once())
            ->method('sendLoanCreatedNotification')
            ->with($loan);

        $this->eventListener->onLoanCreated($event);
    }

    public function testOnLoanReturned(): void
    {
        $loan = $this->createMock(Loan::class);
        $event = new LoanReturnedEvent($loan);

        $this->notificationService
            ->expects($this->once())
            ->method('sendLoanReturnedNotification')
            ->with($loan);

        $this->eventListener->onLoanReturned($event);
    }

    public function testOnBookCreated(): void
    {
        $book = $this->createMock(Book::class);
        $event = new BookCreatedEvent($book);

        $this->notificationService
            ->expects($this->once())
            ->method('sendBookCreatedNotification')
            ->with($book);

        $this->eventListener->onBookCreated($event);
    }
}
