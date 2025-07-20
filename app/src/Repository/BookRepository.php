<?php

namespace App\Repository;

use App\Dto\BookFilterDto;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository implements BookRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function findByFilters(BookFilterDto $filters): array
    {
        $qb = $this->createQueryBuilder('b');

        $this->applyFilters($qb, $filters);

        // Pagination
        $pageSize = 10;
        $offset = ($filters->pageNumber - 1) * $pageSize;
        
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize)
            ->orderBy('b.title', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(BookFilterDto $filters): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)');

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function save(Book $book): void
    {
        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();
    }

    public function findBookById(int $id): ?Book
    {
        return $this->find($id);
    }

    private function applyFilters(&$qb, BookFilterDto $filters): void
    {
        if ($filters->title !== null) {
            $qb->andWhere('b.title LIKE :title')
                ->setParameter('title', '%' . $filters->title . '%');
        }

        if ($filters->author !== null) {
            $qb->andWhere('b.author LIKE :author')
                ->setParameter('author', '%' . $filters->author . '%');
        }

        if ($filters->isbn !== null) {
            $qb->andWhere('b.isbn = :isbn')
                ->setParameter('isbn', $filters->isbn);
        }

        if ($filters->year !== null) {
            $qb->andWhere('b.publicationYear = :year')
                ->setParameter('year', $filters->year);
        }
    }
}
