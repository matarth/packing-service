<?php

declare(strict_types=1);

namespace App\Service\PackingService\ShipmonkSamplePackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\NoPackagingFitsException;
use App\Exception\PackingProviderUnavailableException;
use App\Service\PackingService\PackingServiceInterface;
use App\ValueObject\PackingResult;

class ShipmonkSamplePackingService implements PackingServiceInterface
{
    public function __construct(
        private readonly ShipmonkSamplePackingApiClient $client,
    ) {
    }

    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        $response = $this->client->sendPackRequest(
            $this->createContainers($request->packagings),
            $this->createItems($request),
        );

        if ($response->unpackedItems !== [] || count($response->packedContainers) !== 1) {
            throw new NoPackagingFitsException(
                'The Shipmonk sample packing API did not return one container that fits all products.',
            );
        }

        /** @var array{containerId: string} $packedContainer */
        $packedContainer = $response->packedContainers[0];

        return new PackingResult($packedContainer['containerId']);
    }

    /**
     * @param list<PackagingDTO> $packagings
     * @return list<array{id: string, width: float, length: float, depth: float, maxWeight: float}>
     */
    private function createContainers(array $packagings): array
    {
        $containers = [];
        foreach ($packagings as $packaging) {
            if ($packaging->id === null) {
                throw new PackingProviderUnavailableException('Available packaging must have an identifier.');
            }

            $containers[] = [
                'id' => (string) $packaging->id,
                'width' => $packaging->width,
                'length' => $packaging->length,
                'depth' => $packaging->height,
                'maxWeight' => $packaging->maxWeight,
            ];
        }

        return $containers;
    }

    /** @return list<array{id: string, width: float, length: float, depth: float, weight: float}> */
    private function createItems(PackingRequestDTO $request): array
    {
        $items = [];
        foreach ($request->packingInput->products as $index => $product) {
            $items[] = [
                'id' => sprintf('product-%d', $index),
                'width' => $product->width,
                'length' => $product->length,
                'depth' => $product->height,
                'weight' => $product->weight,
            ];
        }

        return $items;
    }
}
