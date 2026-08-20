<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Exception\PackingProviderException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\CachedPackingServiceDecorator;
use App\Service\PackingService\PackingServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class CachedPackingServiceDecoratorTest extends TestCase
{
    public function testCachesAResultForTheSameRequest(): void
    {
        $request = $this->request();
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult('box-1'));
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

        self::assertSame('box-1', $service->findSmallestBox($request)->containerId);
        self::assertSame('box-1', $service->findSmallestBox($request)->containerId);
    }

    public function testCachesRequestsWithProductsInDifferentOrdersAsOneRequest(): void
    {
        $firstRequest = $this->requestWithProducts([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
            new ProductInput(width: 5.0, height: 6.0, length: 7.0, weight: 8.0),
        ]);
        $secondRequest = $this->requestWithProducts([
            new ProductInput(width: 5.0, height: 6.0, length: 7.0, weight: 8.0),
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ]);
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::once())
            ->method('findSmallestBox')
            ->with($firstRequest)
            ->willReturn(new PackingResult('box-1'));
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

        self::assertSame('box-1', $service->findSmallestBox($firstRequest)->containerId);
        self::assertSame('box-1', $service->findSmallestBox($secondRequest)->containerId);
    }

    public function testCachesEveryRotationOfAProductAsTheSameRequest(): void
    {
        $requests = array_map(
            fn (array $dimensions): PackingRequestDTO => $this->requestWithProducts([
                new ProductInput(...$dimensions, weight: 4.0),
            ]),
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
            ->willReturn(new PackingResult('box-1'));
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

        foreach ($requests as $request) {
            self::assertSame('box-1', $service->findSmallestBox($request)->containerId);
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
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

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
        $products = [new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0)];
        $firstRequest = new PackingRequestDTO(
            new PackingInput($products),
            [new PackagingDTO(id: 1, width: 2.0, height: 3.0, length: 4.0, maxWeight: 5.0)],
        );
        $secondRequest = new PackingRequestDTO(
            new PackingInput($products),
            [new PackagingDTO(id: 1, width: 2.0, height: 3.0, length: 4.0, maxWeight: 6.0)],
        );
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::exactly(2))
            ->method('findSmallestBox')
            ->willReturn(new PackingResult('box-1'), new PackingResult('box-2'));
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

        self::assertSame('box-1', $service->findSmallestBox($firstRequest)->containerId);
        self::assertSame('box-2', $service->findSmallestBox($secondRequest)->containerId);
    }

    public function testCachesResultsAcrossSeparateFilesystemCacheInstances(): void
    {
        $request = $this->request();
        $cacheDirectory = sys_get_temp_dir() . '/packing-cache-' . uniqid('', true);
        $firstProvider = $this->createMock(PackingServiceInterface::class);
        $firstProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult('box-1'));
        $secondProvider = $this->createMock(PackingServiceInterface::class);
        $secondProvider->expects(self::never())->method('findSmallestBox');

        try {
            $firstService = new CachedPackingServiceDecorator(
                $firstProvider,
                new FilesystemAdapter('packing', 300, $cacheDirectory),
            );
            self::assertSame('box-1', $firstService->findSmallestBox($request)->containerId);

            $secondService = new CachedPackingServiceDecorator(
                $secondProvider,
                new FilesystemAdapter('packing', 300, $cacheDirectory),
            );
            self::assertSame('box-1', $secondService->findSmallestBox($request)->containerId);
        } finally {
            (new FilesystemAdapter('packing', 300, $cacheDirectory))->clear();
            rmdir($cacheDirectory);
        }
    }

    private function request(): PackingRequestDTO
    {
        return $this->requestWithProducts([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ]);
    }

    /** @param list<ProductInput> $products */
    private function requestWithProducts(array $products): PackingRequestDTO
    {
        return new PackingRequestDTO(new PackingInput($products), []);
    }
}
