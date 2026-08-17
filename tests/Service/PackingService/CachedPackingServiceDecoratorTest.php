<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\Exception\PackingProviderUnavailableException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\CachedPackingServiceDecorator;
use App\Service\PackingService\PackingServiceInterface;
use App\ValueObject\PackingResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

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

    public function testDoesNotCacheProviderFailures(): void
    {
        $request = $this->request();
        $packingService = $this->createMock(PackingServiceInterface::class);
        $packingService->expects(self::exactly(2))
            ->method('findSmallestBox')
            ->with($request)
            ->willThrowException(new PackingProviderUnavailableException('Unavailable.'));
        $service = new CachedPackingServiceDecorator($packingService, new ArrayAdapter());

        for ($ii = 0; $ii < 2; $ii++) {
            try {
                $service->findSmallestBox($request);
                self::fail('Expected the provider exception to be thrown.');
            } catch (PackingProviderUnavailableException) {
            }
        }
    }

    private function request(): PackingRequestDTO
    {
        return new PackingRequestDTO(
            new PackingInput([
                new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
            ]),
            [],
        );
    }
}
