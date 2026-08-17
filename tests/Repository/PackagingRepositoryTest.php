<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Packaging;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Repository\PackagingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class PackagingRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = require __DIR__ . '/../../src/bootstrap.php';
        $this->entityManager = $entityManager;

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = [$this->entityManager->getClassMetadata(Packaging::class)];
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        foreach (
            [
                [2.5, 3.0, 1.0, 20.0],
                [4.0, 4.0, 4.0, 20.0],
                [2.0, 2.0, 10.0, 20.0],
                [5.5, 6.0, 7.5, 30.0],
                [9.0, 9.0, 9.0, 30.0],
            ] as [$width, $height, $length, $maxWeight]
        ) {
            $this->entityManager->persist(new Packaging($width, $height, $length, $maxWeight));
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
    public function testFiltersOutPackagesThatDoNotHaveEnoughVolume(): void
    {
        $packagings = $this->repository()->findPotentiallyFitting(new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 1.0),
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 1.0),
        ]));

        self::assertSame(
            [2, 3, 4, 5],
            array_map(static fn (Packaging $packaging): ?int => $packaging->getId(), $packagings),
        );
    }

    public function testFiltersOutPackagesThatDoNotMeetNormalizedDimensions(): void
    {
        $packagings = $this->repository()->findPotentiallyFitting(new PackingInput([
            new ProductInput(width: 3.0, height: 3.0, length: 3.0, weight: 1.0),
        ]));

        self::assertSame(
            [2, 4, 5],
            array_map(static fn (Packaging $packaging): ?int => $packaging->getId(), $packagings),
        );
    }

    private function repository(): PackagingRepository
    {
        return new PackagingRepository($this->entityManager);
    }
}
