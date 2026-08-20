<?php

declare(strict_types=1);

namespace App\Service\PackingService\ShipmonkSamplePackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Exception\NoPackagingFitsException;
use App\Exception\PackingProviderException;
use App\Service\PackingService\PackingServiceInterface;

class ShipmonkSamplePackingService implements PackingServiceInterface
{
    /**
     * The sample API accepts integer measurements only. Values are represented
     * in thousandths of the application's input unit, retaining millimetre-like
     * precision without changing the relative dimensions or weights.
     */
    private const INTEGER_UNIT_SCALE = 1000;

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

        foreach ($request->packagings as $packaging) {
            if ((string) $packaging->id === $packedContainer['containerId']) {
                return new PackingResult($packedContainer['containerId']);
            }
        }

        throw new PackingProviderException(
            'The Shipmonk sample packing API returned a container that was not requested.',
        );
    }

    /**
     * @param list<PackagingDTO> $packagings
     * @return list<array{id: string, width: int, length: int, depth: int, maxWeight: int}>
     */
    private function createContainers(array $packagings): array
    {
        $containers = [];
        foreach ($packagings as $packaging) {
            $containers[] = [
                'id' => (string) $packaging->id,
                'width' => self::toIntegerUnit($packaging->width),
                'length' => self::toIntegerUnit($packaging->length),
                'depth' => self::toIntegerUnit($packaging->height),
                'maxWeight' => self::toIntegerUnit($packaging->maxWeight),
            ];
        }

        return $containers;
    }

    /** @return list<array{id: string, width: int, length: int, depth: int, weight: int}> */
    private function createItems(PackingRequestDTO $request): array
    {
        $items = [];
        foreach ($request->packingInput->products as $index => $product) {
            $items[] = [
                'id' => sprintf('product-%d', $index),
                'width' => self::toIntegerUnit($product->width),
                'length' => self::toIntegerUnit($product->length),
                'depth' => self::toIntegerUnit($product->height),
                'weight' => self::toIntegerUnit($product->weight),
            ];
        }

        return $items;
    }

    private static function toIntegerUnit(float $value): int
    {
        $integerValue = round($value * self::INTEGER_UNIT_SCALE, 0, PHP_ROUND_HALF_UP);

        if (!is_finite($integerValue) || $integerValue > PHP_INT_MAX || $integerValue < PHP_INT_MIN) {
            throw new PackingProviderException(
                'Packing measurement cannot be converted to the sample API unit.',
            );
        }

        return (int) $integerValue;
    }
}
