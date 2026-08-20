<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Exception\PackingProviderException;
use LogicException;
use Psr\Log\LoggerInterface;

final readonly class FailoverPackingService implements PackingServiceInterface
{
    /** @param iterable<PackingServiceInterface> $packingServices */
    public function __construct(
        private iterable $packingServices,
        private LoggerInterface $logger,
    ) {
    }

    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        $lastException = null;

        foreach ($this->packingServices as $packingService) {
            try {
                return $packingService->findSmallestBox($request);
            } catch (PackingProviderException $exception) {
                $this->logger->warning('Packing provider unavailable.', [
                    'provider' => $packingService::class,
                    'exception' => $exception,
                ]);
                $lastException = $exception;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new LogicException('No packing service providers are configured.');
    }
}
