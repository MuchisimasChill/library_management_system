<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Loan;
use App\Entity\Book;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Send loan created notification
     */
    public function sendLoanCreatedNotification(Loan $loan): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        $message = sprintf(
            'Loan created: User %s %s (%s) borrowed "%s" by %s',
            $user->getName(),
            $user->getSurname(),
            $user->getEmail(),
            $book->getTitle(),
            $book->getAuthor()
        );

        // In a real application, you would send actual notifications
        // (email, SMS, push notifications, etc.)
        $this->logger->info($message, [
            'event' => 'loan_created',
            'user_id' => $user->getId(),
            'book_id' => $book->getId(),
            'loan_id' => $loan->getId(),
            'loan_date' => $loan->getLoanDate()->format('Y-m-d H:i:s')
        ]);

        // Mock email sending
        $this->sendEmail(
            $user->getEmail(),
            'Book Loan Confirmation',
            $this->generateLoanCreatedEmailTemplate($loan)
        );
    }

    /**
     * Send loan returned notification
     */
    public function sendLoanReturnedNotification(Loan $loan): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        $message = sprintf(
            'Loan returned: User %s %s (%s) returned "%s" by %s',
            $user->getName(),
            $user->getSurname(),
            $user->getEmail(),
            $book->getTitle(),
            $book->getAuthor()
        );

        $this->logger->info($message, [
            'event' => 'loan_returned',
            'user_id' => $user->getId(),
            'book_id' => $book->getId(),
            'loan_id' => $loan->getId(),
            'return_date' => $loan->getReturnedAt()?->format('Y-m-d H:i:s')
        ]);

        // Mock email sending
        $this->sendEmail(
            $user->getEmail(),
            'Book Return Confirmation',
            $this->generateLoanReturnedEmailTemplate($loan)
        );
    }

    /**
     * Send book created notification to librarians
     */
    public function sendBookCreatedNotification(Book $book): void
    {
        $message = sprintf(
            'New book added: "%s" by %s (ISBN: %s)',
            $book->getTitle(),
            $book->getAuthor(),
            $book->getIsbn()
        );

        $this->logger->info($message, [
            'event' => 'book_created',
            'book_id' => $book->getId(),
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'isbn' => $book->getIsbn(),
            'copies' => $book->getNumberOfCopies()
        ]);

        // In real application, notify all librarians
        // $this->notifyLibrarians('New Book Added', $this->generateBookCreatedTemplate($book));
    }

    /**
     * Send overdue notification
     */
    public function sendOverdueNotification(Loan $loan, int $daysOverdue): void
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        $message = sprintf(
            'Overdue loan: User %s %s (%s) has overdue book "%s" (%d days overdue)',
            $user->getName(),
            $user->getSurname(),
            $user->getEmail(),
            $book->getTitle(),
            $daysOverdue
        );

        $this->logger->warning($message, [
            'event' => 'loan_overdue',
            'user_id' => $user->getId(),
            'book_id' => $book->getId(),
            'loan_id' => $loan->getId(),
            'days_overdue' => $daysOverdue
        ]);

        // Mock email sending
        $this->sendEmail(
            $user->getEmail(),
            'Overdue Book Reminder',
            $this->generateOverdueEmailTemplate($loan, $daysOverdue)
        );
    }

    /**
     * Mock email sending method
     * In production, integrate with real email service (Symfony Mailer, etc.)
     */
    private function sendEmail(string $to, string $subject, string $body): void
    {
        $this->logger->info("Mock email sent", [
            'to' => $to,
            'subject' => $subject,
            'body_preview' => substr(strip_tags($body), 0, 100) . '...'
        ]);
    }

    private function generateLoanCreatedEmailTemplate(Loan $loan): string
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        return sprintf(
            "Dear %s,\n\nYou have successfully borrowed the following book:\n\n" .
            "Title: %s\nAuthor: %s\nISBN: %s\nLoan Date: %s\n\n" .
            "Please return the book within 14 days.\n\nBest regards,\nLibrary Management System",
            $user->getName(),
            $book->getTitle(),
            $book->getAuthor(),
            $book->getIsbn(),
            $loan->getLoanDate()->format('Y-m-d')
        );
    }

    private function generateLoanReturnedEmailTemplate(Loan $loan): string
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        return sprintf(
            "Dear %s,\n\nThank you for returning:\n\n" .
            "Title: %s\nAuthor: %s\nReturn Date: %s\n\n" .
            "We hope you enjoyed reading this book!\n\nBest regards,\nLibrary Management System",
            $user->getName(),
            $book->getTitle(),
            $book->getAuthor(),
            $loan->getReturnedAt()?->format('Y-m-d') ?? 'Today'
        );
    }

    private function generateOverdueEmailTemplate(Loan $loan, int $daysOverdue): string
    {
        $user = $loan->getUser();
        $book = $loan->getBook();
        
        return sprintf(
            "Dear %s,\n\nThis is a reminder that the following book is overdue:\n\n" .
            "Title: %s\nAuthor: %s\nLoan Date: %s\nDays Overdue: %d\n\n" .
            "Please return the book as soon as possible to avoid additional fees.\n\n" .
            "Best regards,\nLibrary Management System",
            $user->getName(),
            $book->getTitle(),
            $book->getAuthor(),
            $loan->getLoanDate()->format('Y-m-d'),
            $daysOverdue
        );
    }
}
