<?php

namespace App\Repository;

use App\Entity\Loan;
use App\Enum\LoanStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository implements LoanRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    public function save(Loan $loan): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($loan);
        $entityManager->flush();
    }

    public function updateReturnData(Loan $loan, \DateTimeImmutable $returnedAt, LoanStatus $status): void
    {
        $loan->setReturnedAt($returnedAt);
        $loan->setStatus($status);
        
        $entityManager = $this->getEntityManager();
        $entityManager->flush();
    }
}
