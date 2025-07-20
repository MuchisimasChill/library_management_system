<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const BOOKS_LIST_TTL = 600; // 10 minutes for books list
    private const BOOK_DETAIL_TTL = 1800; // 30 minutes for single book
    private const USER_LOANS_TTL = 300; // 5 minutes for user loans

    public function __construct(
        private readonly CacheInterface $cache
    ) {}

    /**
     * Get cached value or compute and store it
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl ?? self::DEFAULT_TTL);
            return $callback();
        });
    }

    /**
     * Cache books list
     */
    public function cacheBooksList(string $key, callable $callback): mixed
    {
        return $this->get($key, $callback, self::BOOKS_LIST_TTL);
    }

    /**
     * Cache single book
     */
    public function cacheBook(int $bookId, callable $callback): mixed
    {
        $key = "book_detail_{$bookId}";
        return $this->get($key, $callback, self::BOOK_DETAIL_TTL);
    }

    /**
     * Cache user loans
     */
    public function cacheUserLoans(int $userId, int $page, callable $callback): mixed
    {
        $key = "user_loans_{$userId}_page_{$page}";
        return $this->get($key, $callback, self::USER_LOANS_TTL);
    }

    /**
     * Invalidate cache entries by pattern
     */
    public function invalidate(string|array $keys): void
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
    }

    /**
     * Invalidate book-related caches
     */
    public function invalidateBookCaches(?int $bookId = null): void
    {
        // Clear books list cache (all variations)
        $this->invalidate([
            'books_list_all',
            'books_list_filtered'
        ]);

        // Clear specific book cache if ID provided
        if ($bookId !== null) {
            $this->invalidate("book_detail_{$bookId}");
        }
    }

    /**
     * Invalidate loan-related caches
     */
    public function invalidateLoanCaches(int $userId): void
    {
        // We can't easily invalidate all pages, so we'll use a simple approach
        // In production, you might want to use cache tags for this
        for ($page = 1; $page <= 10; $page++) { // Clear first 10 pages
            $this->invalidate("user_loans_{$userId}_page_{$page}");
        }
    }

    /**
     * Generate cache key for books list with filters
     */
    public function generateBooksListKey(array $filters): string
    {
        if (empty($filters)) {
            return 'books_list_all';
        }

        ksort($filters); // Ensure consistent key generation
        return 'books_list_' . md5(serialize($filters));
    }
}
