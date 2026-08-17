<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\Exception\PackingProviderUnavailableException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\FailoverPackingService;
use App\Service\PackingService\PackingServiceInterface;
use App\ValueObject\PackingResult;
use PHPUnit\Framework\TestCase;

final class FailoverPackingServiceTest extends TestCase
{
    public function testUsesTheNextProviderAfterAnUnavailableProvider(): void
    {
        $request = $this->request();
        $unavailableProvider = $this->createMock(PackingServiceInterface::class);
        $unavailableProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willThrowException(new PackingProviderUnavailableException('Unavailable'));
        $availableProvider = $this->createMock(PackingServiceInterface::class);
        $availableProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult('local-box'));

        $service = new FailoverPackingService([$unavailableProvider, $availableProvider]);

        self::assertSame('local-box', $service->findSmallestBox($request)->containerId);
    }

    public function testRethrowsTheLastProviderUnavailableException(): void
    {
        $firstException = new PackingProviderUnavailableException('First provider unavailable.');
        $lastException = new PackingProviderUnavailableException('Last provider unavailable.');
        $firstProvider = $this->createMock(PackingServiceInterface::class);
        $firstProvider->method('findSmallestBox')->willThrowException($firstException);
        $lastProvider = $this->createMock(PackingServiceInterface::class);
        $lastProvider->method('findSmallestBox')->willThrowException($lastException);
        $service = new FailoverPackingService([$firstProvider, $lastProvider]);

        try {
            $service->findSmallestBox($this->request());
            self::fail('Expected the last provider exception to be thrown.');
        } catch (PackingProviderUnavailableException $exception) {
            self::assertSame($lastException, $exception);
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
