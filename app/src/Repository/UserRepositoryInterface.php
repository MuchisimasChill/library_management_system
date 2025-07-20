<?php

namespace App\Repository;

use App\Entity\User;

interface UserRepositoryInterface
{
    /**
     * @return array ['loans' => Loan[], 'totalCount' => int]
     */
    public function getUserLoansHistory(User $user, int $page = 1, int $limit = 10): array;
}
