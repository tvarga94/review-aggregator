<?php

namespace App\Tests\Repository;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReviewRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();
        $this->entityManager->close();

        parent::tearDown();
    }

    public function testFindCompanyStatisticsComputesAverageRatingAndSortsDescending(): void
    {
        $this->persistReview('Alfa Kft.', 5);
        $this->persistReview('Beta Kft.', 4);
        $this->persistReview('Beta Kft.', 4);
        $this->persistReview('Beta Kft.', 4);
        $this->persistReview('Gamma Kft.', 2);
        $this->persistReview('Gamma Kft.', 1);
        $this->entityManager->flush();

        /** @var ReviewRepository $repository */
        $repository = static::getContainer()->get(ReviewRepository::class);
        $stats = $repository->findCompanyStatistics();

        $byCompany = [];
        foreach ($stats as $row) {
            if (\in_array($row['companyName'], ['Alfa Kft.', 'Beta Kft.', 'Gamma Kft.'], true)) {
                $byCompany[$row['companyName']] = $row;
            }
        }

        self::assertSame(1, $byCompany['Alfa Kft.']['reviewCount']);
        self::assertSame(5.0, $byCompany['Alfa Kft.']['averageRating']);
        self::assertSame(3, $byCompany['Beta Kft.']['reviewCount']);
        self::assertSame(4.0, $byCompany['Beta Kft.']['averageRating']);
        self::assertSame(2, $byCompany['Gamma Kft.']['reviewCount']);
        self::assertSame(1.5, $byCompany['Gamma Kft.']['averageRating']);

        $ourCompanyNames = array_values(array_filter(
            array_column($stats, 'companyName'),
            static fn (string $name): bool => \in_array($name, ['Alfa Kft.', 'Beta Kft.', 'Gamma Kft.'], true),
        ));

        self::assertSame(['Alfa Kft.', 'Beta Kft.', 'Gamma Kft.'], $ourCompanyNames);
    }

    private function persistReview(string $companyName, int $rating): void
    {
        $review = new Review();
        $review->setCompanyName($companyName);
        $review->setRating($rating);
        $review->setReviewText('Automatizált repository teszt vélemény.');
        $review->setAuthorEmail('repo-test@example.com');

        $this->entityManager->persist($review);
    }
}
