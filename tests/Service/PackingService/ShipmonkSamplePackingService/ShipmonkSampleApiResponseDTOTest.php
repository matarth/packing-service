<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService\ShipmonkSamplePackingService;

use App\Exception\PackingProviderException;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSampleApiResponseDTO;
use PHPUnit\Framework\TestCase;

final class ShipmonkSampleApiResponseDTOTest extends TestCase
{
    public function testCreatesValidatedResponseFromArray(): void
    {
        $response = ShipmonkSampleApiResponseDTO::fromArray([
            'packedContainers' => [[
                'containerId' => 'box-1',
                'items' => [[
                    'itemId' => 'item-1',
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                    'width' => 1,
                    'length' => 2,
                    'depth' => 3,
                ]],
                'volumeUtilization' => 12.5,
            ]],
            'unpackedItems' => [],
        ]);

        self::assertIsArray($response->packedContainers[0]);
        self::assertSame('box-1', $response->packedContainers[0]['containerId']);
        self::assertSame([], $response->unpackedItems);
    }

    public function testRejectsInvalidPackedItemFromConstructor(): void
    {
        $this->expectException(PackingProviderException::class);

        new ShipmonkSampleApiResponseDTO(
            packedContainers: [[
                'containerId' => 'box-1',
                'items' => [['itemId' => 'item-1']],
                'volumeUtilization' => 12.5,
            ]],
            unpackedItems: [],
        );
    }

    public function testRejectsObjectWhereAnApiListIsExpected(): void
    {
        $this->expectException(PackingProviderException::class);

        ShipmonkSampleApiResponseDTO::fromArray([
            'packedContainers' => ['containerId' => 'box-1'],
            'unpackedItems' => [],
        ]);
    }
}
