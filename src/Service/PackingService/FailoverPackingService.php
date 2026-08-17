<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\Exception\PackingProviderUnavailableException;
use App\ValueObject\PackingResult;
use LogicException;

final readonly class FailoverPackingService implements PackingServiceInterface
{
    /** @param iterable<PackingServiceInterface> $packingServices */
    public function __construct(private iterable $packingServices)
    {
    }

    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        $lastException = null;

        foreach ($this->packingServices as $packingService) {
            try {
                return $packingService->findSmallestBox($request);
            } catch (PackingProviderUnavailableException $exception) {
                $lastException = $exception;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new LogicException('No packing service providers are configured.');
    }
}
