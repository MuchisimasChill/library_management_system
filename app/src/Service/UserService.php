<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CacheService $cacheService
    ) {
    }

    public function getUserLoansHistory(User $user, int $page = 1, int $limit = 10): array
    {
        return $this->cacheService->cacheUserLoans($user->getId(), $page, function () use ($user, $page, $limit) {
            $result = $this->userRepository->getUserLoansHistory($user, $page, $limit);
            
            $totalPages = (int) ceil($result['totalCount'] / $limit);
            
            return [
                'loans' => $result['loans'],
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalCount' => $result['totalCount'],
                    'limit' => $limit
                ]
            ];
        });
    }
}
