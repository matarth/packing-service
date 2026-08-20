<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Entity\Packaging;
use App\Exception\PackingProviderException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\CachedPackingServiceDecorator;
use App\Service\PackingService\PackingServiceInterface;
use App\Tests\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class CachedPackingServiceDecoratorTest extends DatabaseTestCase
{
    public function testCachesAResultForTheSameRequest(): void
    {
        $packaging = $this->packagingBuilder->build();
        $request = $this->requestForPackaging($packaging);
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult((string) $packaging->getId()));
        $service = new CachedPackingServiceDecorator($packingService, $this->entityManager);

        self::assertSame('1', $service->findSmallestBox($request)->containerId);
        self::assertSame('1', $service->findSmallestBox($request)->containerId);
    }

    public function testCachesRequestsWithProductsInDifferentOrdersAsOneRequest(): void
    {
        $packaging = $this->packagingBuilder->build();
        $firstRequest = $this->requestWithProducts([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
            new ProductInput(width: 5.0, height: 6.0, length: 7.0, weight: 8.0),
        ], $packaging);
        $secondRequest = $this->requestWithProducts([
            new ProductInput(width: 5.0, height: 6.0, length: 7.0, weight: 8.0),
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ], $packaging);
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::once())
            ->method('findSmallestBox')
            ->with($firstRequest)
            ->willReturn(new PackingResult((string) $packaging->getId()));
        $service = new CachedPackingServiceDecorator($packingService, $this->entityManager);

        self::assertSame('1', $service->findSmallestBox($firstRequest)->containerId);
        self::assertSame('1', $service->findSmallestBox($secondRequest)->containerId);
    }

    public function testCachesEveryRotationOfAProductAsTheSameRequest(): void
    {
        $packaging = $this->packagingBuilder->build();
        $requests = array_map(
            fn (array $dimensions): PackingRequestDTO => $this->requestWithProducts([
                new ProductInput(...$dimensions, weight: 4.0),
            ], $packaging),
            [
                ['width' => 1.0, 'height' => 2.0, 'length' => 3.0],
                ['width' => 1.0, 'height' => 3.0, 'length' => 2.0],
                ['width' => 2.0, 'height' => 1.0, 'length' => 3.0],
                ['width' => 2.0, 'height' => 3.0, 'length' => 1.0],
                ['width' => 3.0, 'height' => 1.0, 'length' => 2.0],
                ['width' => 3.0, 'height' => 2.0, 'length' => 1.0],
            ],
        );
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::once())
            ->method('findSmallestBox')
            ->with($requests[0])
            ->willReturn(new PackingResult((string) $packaging->getId()));
        $service = new CachedPackingServiceDecorator($packingService, $this->entityManager);

        foreach ($requests as $request) {
            self::assertSame('1', $service->findSmallestBox($request)->containerId);
        }
    }

    public function testDoesNotCacheProviderFailures(): void
    {
        $request = $this->request();
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::exactly(2))
            ->method('findSmallestBox')
            ->with($request)
            ->willThrowException(new PackingProviderException('Unavailable.'));
        $service = new CachedPackingServiceDecorator($packingService, $this->entityManager);

        for ($ii = 0; $ii < 2; $ii++) {
            try {
                $service->findSmallestBox($request);
                self::fail('Expected the provider exception to be thrown.');
            } catch (PackingProviderException) {
            }
        }
    }

    public function testDoesNotShareCachedResultsBetweenDifferentAvailableBoxes(): void
    {
        $packaging = $this->packagingBuilder->build();
        $products = [new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0)];
        $firstRequest = new PackingRequestDTO(
            new PackingInput($products),
            [new PackagingDTO(
                id: $packaging->getId(),
                width: 2.0,
                height: 3.0,
                length: 4.0,
                maxWeight: 5.0,
            )],
        );
        $secondRequest = new PackingRequestDTO(
            new PackingInput($products),
            [new PackagingDTO(
                id: $packaging->getId(),
                width: 2.0,
                height: 3.0,
                length: 4.0,
                maxWeight: 6.0,
            )],
        );
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::exactly(2))
            ->method('findSmallestBox')
            ->willReturn(new PackingResult((string) $packaging->getId()));
        $service = new CachedPackingServiceDecorator($packingService, $this->entityManager);

        self::assertSame('1', $service->findSmallestBox($firstRequest)->containerId);
        self::assertSame('1', $service->findSmallestBox($secondRequest)->containerId);
    }

    public function testCachesResultsAcrossSeparateEntityManagerInstances(): void
    {
        $request = $this->request();
        $packagingId = (string) $request->packagings[0]->id;
        $firstProvider = $this->createMock(PackingServiceInterface::class);
        $firstProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult($packagingId));
        $secondProvider = $this->createMock(PackingServiceInterface::class);
        $secondProvider->expects(self::never())->method('findSmallestBox');
        $firstService = new CachedPackingServiceDecorator($firstProvider, $this->entityManager);

        self::assertSame($packagingId, $firstService->findSmallestBox($request)->containerId);

        /** @var EntityManagerInterface $secondEntityManager */
        $secondEntityManager = require __DIR__ . '/../../../src/bootstrap.php';
        try {
            $secondService = new CachedPackingServiceDecorator($secondProvider, $secondEntityManager);
            self::assertSame($packagingId, $secondService->findSmallestBox($request)->containerId);
        } finally {
            $secondEntityManager->close();
        }
    }

    private function request(): PackingRequestDTO
    {
        return $this->requestWithProducts([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ], $this->packagingBuilder->build());
    }

    private function requestForPackaging(Packaging $packaging): PackingRequestDTO
    {
        return new PackingRequestDTO(
            new PackingInput([
                new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
            ]),
            [PackagingDTO::withNormalizedRotation($packaging)],
        );
    }

    /** @param list<ProductInput> $products */
    private function requestWithProducts(array $products, Packaging $packaging): PackingRequestDTO
    {
        return new PackingRequestDTO(
            new PackingInput($products),
            [PackagingDTO::withNormalizedRotation($packaging)],
        );
    }
}
