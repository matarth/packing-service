<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService\ShipmonkSamplePackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\NoPackagingFitsException;
use App\Exception\PackingProviderUnavailableException;
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
                'width' => 4500,
                'length' => 6125,
                'depth' => 5250,
                'maxWeight' => 7875,
            ]],
            'items' => [[
                'id' => 'product-0',
                'width' => 1250,
                'length' => 3750,
                'depth' => 2500,
                'weight' => 4125,
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

    public function testRejectsAResponseWithAnUnknownContainer(): void
    {
        $handler = new MockHandler([
            new Response(200, [], '{"packedContainers":[{"containerId":"unknown","items":[]}],"unpackedItems":[]}'),
        ]);
        $service = new ShipmonkSamplePackingService(
            new ShipmonkSamplePackingApiClient(new Client([
                'handler' => HandlerStack::create($handler),
                'http_errors' => false,
            ])),
        );

        $this->expectException(PackingProviderUnavailableException::class);
        $service->findSmallestBox($this->request());
    }

    private function request(): PackingRequestDTO
    {
        return new PackingRequestDTO(
            new PackingInput([
                new ProductInput(width: 1.25, height: 2.5, length: 3.75, weight: 4.125),
            ]),
            [new PackagingDTO(id: 1, width: 4.5, height: 5.25, length: 6.125, maxWeight: 7.875)],
        );
    }
}
