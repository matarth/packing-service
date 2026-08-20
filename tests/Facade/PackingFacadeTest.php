<?php

declare(strict_types=1);

namespace App\Tests\Facade;

use App\Facade\PackingFacade;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Repository\PackagingRepository;
use App\Service\PackingService\PackingServiceInterface;
use PHPUnit\Framework\TestCase;

final class PackingFacadeTest extends TestCase
{
    public function testReturnsNoFitErrorWithoutCallingThePackingService(): void
    {
        $input = new PackingInput([
            new ProductInput(width: 10.0, height: 10.0, length: 10.0, weight: 4.0),
        ]);
        $repository = $this->createMock(PackagingRepository::class);
        $repository->expects(self::once())->method('findPotentiallyFitting')->with($input)->willReturn([]);
        $service = $this->createMock(PackingServiceInterface::class);
        $service->expects(self::never())->method('findSmallestBox');
        $facade = new PackingFacade($service, $repository);

        self::assertSame([
            'data' => null,
            'error' => [
                'code' => 'no_packaging_fits',
                'message' => 'No available packaging can fit the products.',
            ],
        ], $facade->findSmallestBox($input)->jsonSerialize());
    }

    public function testReturnsStructuredErrorForUnexpectedOperationalFailure(): void
    {
        $input = new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ]);
        $repository = $this->createMock(PackagingRepository::class);
        $repository->method('findPotentiallyFitting')->willThrowException(
            new \RuntimeException('Database unavailable.'),
        );
        $service = $this->createMock(PackingServiceInterface::class);
        $facade = new PackingFacade($service, $repository);

        self::assertSame([
            'data' => null,
            'error' => [
                'code' => 'operational_error',
                'message' => 'Unable to calculate packaging at this time.',
            ],
        ], $facade->findSmallestBox($input)->jsonSerialize());
    }
}
