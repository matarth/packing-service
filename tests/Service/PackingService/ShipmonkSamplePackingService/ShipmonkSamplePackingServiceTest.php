<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService\ShipmonkSamplePackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\NoPackagingFitsException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingApiClient;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ShipmonkSamplePackingServiceTest extends TestCase
{
    public function testFindsTheContainerThatFitsAllProducts(): void
    {
        $handler = new MockHandler([
            new Response(200, [], json_encode([
                'packedContainers' => [[
                    'containerId' => '1',
                    'items' => [[
                        'itemId' => 'product-0',
                        'x' => 0,
                        'y' => 0,
                        'z' => 0,
                        'width' => 1,
                        'length' => 3,
                        'depth' => 2,
                    ]],
                    'volumeUtilization' => 10.0,
                ]],
                'unpackedItems' => [],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $service = new ShipmonkSamplePackingService(
            new ShipmonkSamplePackingApiClient(new Client([
                'handler' => HandlerStack::create($handler),
                'http_errors' => false,
            ])),
        );

        $result = $service->findSmallestBox($this->request());

        self::assertSame('1', $result->containerId);
        $httpRequest = $handler->getLastRequest();
        self::assertNotNull($httpRequest);
        self::assertSame([
            'containers' => [[
                'id' => '1',
                'width' => 4,
                'length' => 6,
                'depth' => 5,
                'maxWeight' => 7,
            ]],
            'items' => [[
                'id' => 'product-0',
                'width' => 1,
                'length' => 3,
                'depth' => 2,
                'weight' => 4,
            ]],
        ], json_decode((string) $httpRequest->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRejectsAResponseThatDoesNotPackEveryProduct(): void
    {
        $handler = new MockHandler([
            new Response(200, [], '{"packedContainers":[],"unpackedItems":["product-0"]}'),
        ]);
        $service = new ShipmonkSamplePackingService(
            new ShipmonkSamplePackingApiClient(new Client([
                'handler' => HandlerStack::create($handler),
                'http_errors' => false,
            ])),
        );

        $this->expectException(NoPackagingFitsException::class);
        $service->findSmallestBox($this->request());
    }

    private function request(): PackingRequestDTO
    {
        return new PackingRequestDTO(
            new PackingInput([
                new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
            ]),
            [new PackagingDTO(id: 1, width: 4.0, height: 5.0, length: 6.0, maxWeight: 7.0)],
        );
    }
}
