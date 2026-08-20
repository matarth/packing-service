<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Exception\NoPackagingFitsException;
use App\Exception\PackingProviderException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\FailoverPackingService;
use App\Service\PackingService\PackingServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class FailoverPackingServiceTest extends TestCase
{
    public function testDoesNotLogWhenTheFirstProviderSucceeds(): void
    {
        $request = $this->request();
        $provider = $this->createMock(PackingServiceInterface::class);
        $provider->method('findSmallestBox')->with($request)->willReturn(new PackingResult('box'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $service = new FailoverPackingService([$provider], $logger);

        self::assertSame('box', $service->findSmallestBox($request)->containerId);
    }

    public function testUsesTheNextProviderAfterAnUnavailableProvider(): void
    {
        $request = $this->request();
        $unavailableProvider = $this->createMock(PackingServiceInterface::class);
        $unavailableProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willThrowException(new PackingProviderException('Unavailable'));
        $availableProvider = $this->createMock(PackingServiceInterface::class);
        $availableProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willReturn(new PackingResult('local-box'));

        $service = new FailoverPackingService([$unavailableProvider, $availableProvider], new NullLogger());

        self::assertSame('local-box', $service->findSmallestBox($request)->containerId);
    }

    public function testLogsAnUnavailableProviderBeforeUsingTheNextProvider(): void
    {
        $request = $this->request();
        $exception = new PackingProviderException('Unavailable');
        $unavailableProvider = $this->createMock(PackingServiceInterface::class);
        $unavailableProvider->method('findSmallestBox')->with($request)->willThrowException($exception);
        $availableProvider = $this->createMock(PackingServiceInterface::class);
        $availableProvider->method('findSmallestBox')->with($request)->willReturn(new PackingResult('local-box'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('Packing provider unavailable.', [
                'provider' => $unavailableProvider::class,
                'exception' => $exception,
            ]);
        $service = new FailoverPackingService([$unavailableProvider, $availableProvider], $logger);

        self::assertSame('local-box', $service->findSmallestBox($request)->containerId);
    }

    public function testRethrowsTheLastProviderUnavailableException(): void
    {
        $firstException = new PackingProviderException('First provider unavailable.');
        $lastException = new PackingProviderException('Last provider unavailable.');
        $firstProvider = $this->createMock(PackingServiceInterface::class);
        $firstProvider->method('findSmallestBox')->willThrowException($firstException);
        $lastProvider = $this->createMock(PackingServiceInterface::class);
        $lastProvider->method('findSmallestBox')->willThrowException($lastException);
        $service = new FailoverPackingService([$firstProvider, $lastProvider], new NullLogger());

        try {
            $service->findSmallestBox($this->request());
            self::fail('Expected the last provider exception to be thrown.');
        } catch (PackingProviderException $exception) {
            self::assertSame($lastException, $exception);
        }
    }

    public function testDoesNotTryAnotherProviderWhenNoPackagingFits(): void
    {
        $request = $this->request();
        $noFitException = new NoPackagingFitsException('No packaging fits the products.');
        $noFitProvider = $this->createMock(PackingServiceInterface::class);
        $noFitProvider->expects(self::once())
            ->method('findSmallestBox')
            ->with($request)
            ->willThrowException($noFitException);
        $nextProvider = $this->createMock(PackingServiceInterface::class);
        $nextProvider->expects(self::never())->method('findSmallestBox');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');
        $service = new FailoverPackingService([$noFitProvider, $nextProvider], $logger);

        try {
            $service->findSmallestBox($request);
            self::fail('Expected the no-fit exception to be thrown.');
        } catch (NoPackagingFitsException $exception) {
            self::assertSame($noFitException, $exception);
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
