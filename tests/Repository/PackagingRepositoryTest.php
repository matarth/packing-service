<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Packaging;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Repository\PackagingRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class PackagingRepositoryTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (
            [
                [2.5, 3.0, 1.0, 20.0],
                [4.0, 4.0, 4.0, 20.0],
                [2.0, 2.0, 10.0, 20.0],
                [5.5, 6.0, 7.5, 30.0],
                [9.0, 9.0, 9.0, 30.0],
            ] as [$width, $height, $length, $maxWeight]
        ) {
            $this->packagingBuilder
                ->withWidth($width)
                ->withHeight($height)
                ->withLength($length)
                ->withMaxWeight($maxWeight)
                ->build();
        }
    }

    public function testFiltersOutPackagesThatDoNotHaveEnoughVolume(): void
    {
        $packagings = $this->repository()->findPotentiallyFitting(new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 1.0),
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 1.0),
        ]));

        self::assertSame(
            [2, 3, 4, 5],
            array_map(static fn (Packaging $packaging): int => $packaging->getId(), $packagings),
        );
    }

    public function testFiltersOutPackagesThatDoNotMeetNormalizedDimensions(): void
    {
        $packagings = $this->repository()->findPotentiallyFitting(new PackingInput([
            new ProductInput(width: 3.0, height: 3.0, length: 3.0, weight: 1.0),
        ]));

        self::assertSame(
            [2, 4, 5],
            array_map(static fn (Packaging $packaging): int => $packaging->getId(), $packagings),
        );
    }

    public function testFiltersOutPackagesThatCannotCarryTheTotalProductWeight(): void
    {
        $packagings = $this->repository()->findPotentiallyFitting(new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 10.0),
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 11.0),
        ]));

        self::assertSame(
            [4, 5],
            array_map(static fn (Packaging $packaging): int => $packaging->getId(), $packagings),
        );
    }

    #[DataProvider('isolationRuns')]
    public function testDoesNotLeakDatabaseStateBetweenTests(float $maxWeight): void
    {
        self::assertCount(5, $this->repository()->findAll());

        $this->packagingBuilder
            ->withWidth(10.0)
            ->withHeight(10.0)
            ->withLength(10.0)
            ->withMaxWeight($maxWeight)
            ->build();

        self::assertCount(6, $this->repository()->findAll());
    }

    /** @return iterable<string, array{float}> */
    public static function isolationRuns(): iterable
    {
        yield 'first isolated test' => [40.0];
        yield 'second isolated test' => [50.0];
    }

    private function repository(): PackagingRepository
    {
        return new PackagingRepository($this->entityManager);
    }
}
