<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService\ShipmonkSamplePackingService;

use App\Exception\InvalidInputException;
use App\Exception\PackingProviderException;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class ShipmonkSamplePackingApiClientTest extends TestCase
{
    public function testRateLimitIsSharedAcrossApiClientInstances(): void
    {
        $rateLimitCache = new ArrayAdapter();
        $request = [
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        ];

        $rateLimitedClient = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(429, '{}', ['Retry-After' => '60']),
            rateLimitCache: $rateLimitCache,
        );

        try {
            $rateLimitedClient->sendPackRequest(...$request);
            self::fail('Expected the rate-limited provider exception to be thrown.');
        } catch (PackingProviderException) {
        }

        $availableClient = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(
                200,
                '{"packedContainers":[],"unpackedItems":["item"]}',
            ),
            rateLimitCache: $rateLimitCache,
        );

        $this->expectException(PackingProviderException::class);
        $availableClient->sendPackRequest(...$request);
    }

    public function testApiIsCalledAgainWhenRateLimitExpires(): void
    {
        $handler = new MockHandler([
            new Response(429, ['Retry-After' => '0'], '{}'),
            new Response(200, [], '{"packedContainers":[],"unpackedItems":["item"]}'),
        ]);
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: new Client([
                'handler' => HandlerStack::create($handler),
                'http_errors' => false,
            ]),
            rateLimitCache: new ArrayAdapter(),
        );
        $request = [
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        ];

        try {
            $client->sendPackRequest(...$request);
            self::fail('Expected the rate-limited provider exception to be thrown.');
        } catch (PackingProviderException) {
        }

        self::assertSame(['item'], $client->sendPackRequest(...$request)->unpackedItems);
    }

    public function testCallsApiWhenRateLimitCacheCannotBeRead(): void
    {
        $rateLimitCache = $this->createMock(CacheInterface::class);
        $rateLimitCache->expects(self::once())
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache unavailable.'));
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(
                200,
                '{"packedContainers":[],"unpackedItems":["item"]}',
            ),
            rateLimitCache: $rateLimitCache,
        );

        $response = $client->sendPackRequest(
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        );

        self::assertSame(['item'], $response->unpackedItems);
    }

    public function testSendsDocumentedRequestAndReturnsPackingResponse(): void
    {
        $handler = new MockHandler([new Response(200, [], '{"packedContainers":[],"unpackedItems":["item-1"]}')]);
        $httpClient = new Client([
            'handler' => HandlerStack::create($handler),
            'http_errors' => false,
        ]);
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: $httpClient,
            rateLimitCache: $this->emptyRateLimitCache(),
        );

        $response = $client->sendPackRequest(
            [['id' => 'box-1', 'width' => 10, 'length' => 20, 'depth' => 30, 'maxWeight' => 100]],
            [['id' => 'item-1', 'width' => 1, 'length' => 2, 'depth' => 3, 'weight' => 4]],
        );

        self::assertSame([], $response->packedContainers);
        self::assertSame(['item-1'], $response->unpackedItems);
        $request = $handler->getLastRequest();
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame([
            'containers' => [[
                'id' => 'box-1', 'width' => 10, 'length' => 20, 'depth' => 30, 'maxWeight' => 100,
            ]],
            'items' => [[
                'id' => 'item-1', 'width' => 1, 'length' => 2, 'depth' => 3, 'weight' => 4,
            ]],
        ], json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testTreatsUnavailableResponsesAsProviderFailures(): void
    {
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(500, '{}'),
            rateLimitCache: $this->emptyRateLimitCache(),
        );

        $this->expectException(PackingProviderException::class);
        $client->sendPackRequest(
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        );
    }

    public function testTreatsMalformedSuccessfulResponsesAsProviderFailures(): void
    {
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(
                200,
                '{"packedContainers":[{"containerId":"box"}],"unpackedItems":[]}',
            ),
            rateLimitCache: $this->emptyRateLimitCache(),
        );

        $this->expectException(PackingProviderException::class);
        $client->sendPackRequest(
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        );
    }

    public function testTreatsRejectedRequestsAsInvalidInput(): void
    {
        $client = new ShipmonkSamplePackingApiClient(
            httpClient: $this->httpClientResponding(422, '{}'),
            rateLimitCache: $this->emptyRateLimitCache(),
        );

        $this->expectException(InvalidInputException::class);
        $client->sendPackRequest(
            [['id' => 'box', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]],
        );
    }

    /** @param array<string, string> $headers */
    private function httpClientResponding(int $statusCode, string $body, array $headers = []): ClientInterface
    {
        return new Client([
            'handler' => HandlerStack::create(new MockHandler([new Response($statusCode, $headers, $body)])),
            'http_errors' => false,
        ]);
    }

    private function emptyRateLimitCache(): ArrayAdapter
    {
        return new ArrayAdapter();
    }
}
