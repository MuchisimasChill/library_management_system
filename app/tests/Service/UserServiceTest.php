<?php

namespace App\Tests\Service;

use App\Entity\Loan;
use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Service\UserService;
use App\Service\CacheService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private CacheService|MockObject $cacheService;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->cacheService = $this->createMock(CacheService::class);
        $this->userService = new UserService($this->userRepository, $this->cacheService);
    }

    public function testGetUserLoansHistory(): void
    {
        // Arrange
        $user = $this->createUserEntity(1, 'John', 'Doe', 'john@example.com');
        $page = 1;
        $limit = 10;
        
        $loans = [
            $this->createMock(Loan::class),
            $this->createMock(Loan::class),
        ];
        
        $repositoryResult = [
            'loans' => $loans,
            'totalCount' => 2
        ];
        
        $this->cacheService
            ->expects($this->once())
            ->method('cacheUserLoans')
            ->with($user->getId(), $page)
            ->willReturnCallback(function ($userId, $page, $callback) {
                return $callback();
            });
        
        $this->userRepository
            ->expects($this->once())
            ->method('getUserLoansHistory')
            ->with($user, $page, $limit)
            ->willReturn($repositoryResult);

        // Act
        $result = $this->userService->getUserLoansHistory($user, $page, $limit);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('loans', $result);
        $this->assertArrayHasKey('pagination', $result);
        
        $this->assertSame($loans, $result['loans']);
        
        $pagination = $result['pagination'];
        $this->assertSame(1, $pagination['currentPage']);
        $this->assertSame(1, $pagination['totalPages']);
        $this->assertSame(2, $pagination['totalCount']);
        $this->assertSame(10, $pagination['limit']);
    }

    public function testGetUserLoansHistoryWithMultiplePages(): void
    {
        // Arrange
        $user = $this->createUserEntity(1, 'John', 'Doe', 'john@example.com');
        $page = 2;
        $limit = 10;
        
        $loans = [
            $this->createMock(Loan::class),
        ];
        
        $repositoryResult = [
            'loans' => $loans,
            'totalCount' => 25
        ];
        
        $this->cacheService
            ->expects($this->once())
            ->method('cacheUserLoans')
            ->with($user->getId(), $page)
            ->willReturnCallback(function ($userId, $page, $callback) {
                return $callback();
            });
        
        $this->userRepository
            ->expects($this->once())
            ->method('getUserLoansHistory')
            ->with($user, $page, $limit)
            ->willReturn($repositoryResult);

        // Act
        $result = $this->userService->getUserLoansHistory($user, $page, $limit);

        // Assert
        $this->assertIsArray($result);
        $pagination = $result['pagination'];
        $this->assertSame(2, $pagination['currentPage']);
        $this->assertSame(3, $pagination['totalPages']); // ceil(25/10) = 3
        $this->assertSame(25, $pagination['totalCount']);
        $this->assertSame(10, $pagination['limit']);
    }

    public function testGetUserLoansHistoryDefaultPagination(): void
    {
        // Arrange
        $user = $this->createUserEntity(1, 'John', 'Doe', 'john@example.com');
        
        $loans = [];
        $repositoryResult = [
            'loans' => $loans,
            'totalCount' => 0
        ];
        
        $this->cacheService
            ->expects($this->once())
            ->method('cacheUserLoans')
            ->with($user->getId(), 1)
            ->willReturnCallback(function ($userId, $page, $callback) {
                return $callback();
            });
        
        $this->userRepository
            ->expects($this->once())
            ->method('getUserLoansHistory')
            ->with($user, 1, 10) // default values
            ->willReturn($repositoryResult);

        // Act
        $result = $this->userService->getUserLoansHistory($user);

        // Assert
        $this->assertIsArray($result);
        $pagination = $result['pagination'];
        $this->assertSame(1, $pagination['currentPage']);
        $this->assertSame(0, $pagination['totalPages']);
        $this->assertSame(0, $pagination['totalCount']);
        $this->assertSame(10, $pagination['limit']);
    }

    private function createUserEntity(int $id, string $name, string $surname, string $email): User
    {
        $user = new User();
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($email);
        
        // Use reflection to set the ID
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $id);
        
        return $user;
    }
}
