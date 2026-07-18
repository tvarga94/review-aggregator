<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Returns reviews ordered by newest first, optionally filtered by company name.
     *
     * @return Review[]
     */
    public function findLatest(?string $companyNameSearch = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        if (null !== $companyNameSearch && '' !== trim($companyNameSearch)) {
            $qb->andWhere('r.companyName LIKE :companyNameSearch')
                ->setParameter('companyNameSearch', '%'.trim($companyNameSearch).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns per-company aggregate statistics (review count and average rating),
     * ordered by average rating descending.
     *
     * @return array<int, array{companyName: string, reviewCount: int, averageRating: float}>
     */
    public function findCompanyStatistics(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.companyName AS companyName')
            ->addSelect('COUNT(r.id) AS reviewCount')
            ->addSelect('AVG(r.rating) AS averageRating')
            ->groupBy('r.companyName')
            ->orderBy('averageRating', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $row): array => [
                'companyName' => $row['companyName'],
                'reviewCount' => (int) $row['reviewCount'],
                'averageRating' => round((float) $row['averageRating'], 2),
            ],
            $rows,
        );
    }
}
