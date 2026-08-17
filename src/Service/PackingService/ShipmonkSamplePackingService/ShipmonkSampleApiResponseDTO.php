<?php

declare(strict_types=1);

namespace App\Service\PackingService\ShipmonkSamplePackingService;

use App\Exception\PackingProviderUnavailableException;

final readonly class ShipmonkSampleApiResponseDTO
{
    /**
     * @param array<mixed> $packedContainers
     * @param array<mixed> $unpackedItems
     */
    public function __construct(
        public array $packedContainers,
        public array $unpackedItems,
    ) {
        if (!array_is_list($packedContainers) || !array_is_list($unpackedItems)) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API returned an invalid response.',
            );
        }

        self::validatePackedContainers($packedContainers);
        self::validateUnpackedItems($unpackedItems);
    }

    /** @param array<string, mixed> $response */
    public static function fromArray(array $response): self
    {
        $packedContainers = $response['packedContainers'] ?? null;
        $unpackedItems = $response['unpackedItems'] ?? null;

        if (
            !is_array($packedContainers) || !array_is_list($packedContainers) ||
            !is_array($unpackedItems) || !array_is_list($unpackedItems)
        ) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API returned an invalid response.',
            );
        }

        return new self($packedContainers, $unpackedItems);
    }

    /** @param array<mixed> $unpackedItems */
    private static function validateUnpackedItems(array $unpackedItems): void
    {
        foreach ($unpackedItems as $itemId) {
            if (!is_string($itemId)) {
                throw new PackingProviderUnavailableException(
                    'The Shipmonk sample packing API returned an invalid response.',
                );
            }
        }
    }

    /** @param array<mixed> $packedContainers */
    private static function validatePackedContainers(array $packedContainers): void
    {
        foreach ($packedContainers as $packedContainer) {
            if (!is_array($packedContainer) || array_is_list($packedContainer)) {
                throw new PackingProviderUnavailableException(
                    'The Shipmonk sample packing API returned an invalid response.',
                );
            }

            if (
                !isset($packedContainer['containerId']) || !is_string($packedContainer['containerId']) ||
                !isset($packedContainer['items']) || !is_array($packedContainer['items']) ||
                !array_is_list($packedContainer['items']) ||
                !isset($packedContainer['volumeUtilization']) ||
                (!is_int($packedContainer['volumeUtilization']) && !is_float($packedContainer['volumeUtilization']))
            ) {
                throw new PackingProviderUnavailableException(
                    'The Shipmonk sample packing API returned an invalid response.',
                );
            }

            foreach ($packedContainer['items'] as $item) {
                if (!is_array($item) || array_is_list($item)) {
                    throw new PackingProviderUnavailableException(
                        'The Shipmonk sample packing API returned an invalid response.',
                    );
                }

                self::validatePackedItem($item);
            }
        }
    }

    /** @param array<mixed> $item */
    private static function validatePackedItem(array $item): void
    {
        if (!isset($item['itemId']) || !is_string($item['itemId'])) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API returned an invalid response.',
            );
        }

        foreach (['x', 'y', 'z', 'width', 'length', 'depth'] as $field) {
            if (!isset($item[$field]) || !is_int($item[$field])) {
                throw new PackingProviderUnavailableException(
                    'The Shipmonk sample packing API returned an invalid response.',
                );
            }
        }
    }
}
