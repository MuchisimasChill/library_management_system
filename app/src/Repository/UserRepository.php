<?php

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUserLoansHistory(User $user, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->getEntityManager()
            ->getRepository(Loan::class)
            ->createQueryBuilder('l')
            ->where('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.loanDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $loans = $queryBuilder->getQuery()->getResult();

        $totalQueryBuilder = $this->getEntityManager()
            ->getRepository(Loan::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.user = :user')
            ->setParameter('user', $user);

        $totalCount = (int) $totalQueryBuilder->getQuery()->getSingleScalarResult();

        return [
            'loans' => $loans,
            'totalCount' => $totalCount
        ];
    }
}
