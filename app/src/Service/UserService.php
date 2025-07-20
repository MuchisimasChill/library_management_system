<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function getUserLoansHistory(User $user, int $page = 1, int $limit = 10): array
    {
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
    }
}
