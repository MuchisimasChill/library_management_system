<?php

namespace App\Tests\Service;

use App\Service\CacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheServiceTest extends TestCase
{
    private CacheInterface $cache;
    private CacheService $cacheService;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->cacheService = new CacheService($this->cache);
    }

    public function testGet(): void
    {
        $key = 'test_key';
        $expectedValue = 'test_value';
        $callback = fn() => $expectedValue;

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with($key, $this->isCallable())
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600); // Default TTL
                
                return $callback($item);
            });

        $result = $this->cacheService->get($key, $callback);

        $this->assertEquals($expectedValue, $result);
    }

    public function testGenerateBooksListKey(): void
    {
        // Test with empty filters
        $key1 = $this->cacheService->generateBooksListKey([]);
        $this->assertEquals('books_list_all', $key1);

        // Test with filters
        $filters = ['title' => 'Harry', 'author' => 'Rowling', 'pageNumber' => 1];
        $key2 = $this->cacheService->generateBooksListKey($filters);
        $this->assertStringStartsWith('books_list_', $key2);
        $this->assertNotEquals('books_list_all', $key2);

        // Test that same filters produce same key
        $key3 = $this->cacheService->generateBooksListKey($filters);
        $this->assertEquals($key2, $key3);

        // Test that different order produces same key
        $filtersReordered = ['pageNumber' => 1, 'author' => 'Rowling', 'title' => 'Harry'];
        $key4 = $this->cacheService->generateBooksListKey($filtersReordered);
        $this->assertEquals($key2, $key4);
    }

    public function testInvalidate(): void
    {
        $keys = ['key1', 'key2'];

        $this->cache
            ->expects($this->exactly(2))
            ->method('delete')
            ->with($this->callback(function ($key) {
                return in_array($key, ['key1', 'key2']);
            }));

        $this->cacheService->invalidate($keys);
    }

    public function testInvalidateSingleKey(): void
    {
        $key = 'single_key';

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->cacheService->invalidate($key);
    }
}
