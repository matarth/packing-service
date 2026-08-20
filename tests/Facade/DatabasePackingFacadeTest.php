<?php

declare(strict_types=1);

namespace App\Tests\Facade;

use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Exception\PackingProviderException;
use App\Facade\PackingFacade;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Repository\PackagingRepository;
use App\Service\PackingService\PackingServiceInterface;
use App\Tests\DatabaseTestCase;

final class DatabasePackingFacadeTest extends DatabaseTestCase
{
    public function testBuildsServiceRequestWithAvailablePackagings(): void
    {
        $input = new PackingInput([
            new ProductInput(width: 3.0, height: 1.0, length: 2.0, weight: 4.0),
        ]);
        $packagings = [$this->packagingBuilder
            ->withWidth(2.5)
            ->withHeight(3.0)
            ->withLength(1.0)
            ->withMaxWeight(20.0)
            ->build()];
        $repository = $this->createMock(PackagingRepository::class);
        $repository->expects(self::once())->method('findPotentiallyFitting')->with($input)->willReturn($packagings);
        $service = $this->createMock(PackingServiceInterface::class);
        $service->expects(self::once())
            ->method('findSmallestBox')
            ->with(self::callback(static function (PackingRequestDTO $request) use ($input): bool {
                return $request->packingInput === $input
                    && $request->packingInput->products[0]->width === 1.0
                    && $request->packingInput->products[0]->height === 2.0
                    && $request->packingInput->products[0]->length === 3.0
                    && count($request->packagings) === 1
                    && $request->packagings[0]->id === 1
                    && $request->packagings[0]->width === 1.0
                    && $request->packagings[0]->height === 2.5
                    && $request->packagings[0]->length === 3.0;
            }))
            ->willReturn(new PackingResult('small-box'));

        $facade = new PackingFacade($service, $repository);

        self::assertSame([
            'data' => ['box' => 'small-box'],
            'error' => null,
        ], $facade->findSmallestBox($input)->jsonSerialize());
    }

    public function testReturnsStructuredErrorWhenAllPackingProvidersAreUnavailable(): void
    {
        $input = new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ]);
        $repository = $this->createMock(PackagingRepository::class);
        $packaging = $this->packagingBuilder
            ->withWidth(2.5)
            ->withHeight(3.0)
            ->withLength(1.0)
            ->withMaxWeight(20.0)
            ->build();
        $repository->method('findPotentiallyFitting')->willReturn([$packaging]);
        $service = $this->createMock(PackingServiceInterface::class);
        $service->method('findSmallestBox')->willThrowException(
            new PackingProviderException('Provider unavailable.'),
        );

        $facade = new PackingFacade($service, $repository);

        self::assertSame([
            'data' => null,
            'error' => [
                'code' => 'packing_provider_unavailable',
                'message' => 'Provider unavailable.',
            ],
        ], $facade->findSmallestBox($input)->jsonSerialize());
    }
}
