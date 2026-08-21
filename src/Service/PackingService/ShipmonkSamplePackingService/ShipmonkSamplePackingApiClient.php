<?php

declare(strict_types=1);

namespace App\Service\PackingService\ShipmonkSamplePackingService;

use App\Exception\InvalidInputException;
use App\Exception\PackingProviderException;
use GuzzleHttp\ClientInterface;
use JsonException;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class ShipmonkSamplePackingApiClient
{
    public const DEFAULT_ENDPOINT = 'https://binpacking.janedbal.cz/api/v1/pack';
    private const RATE_LIMIT_EXCEEDED_CACHE_KEY = 'rate_limit_exceeded';

    public function __construct(
        private ClientInterface $httpClient,
        private CacheInterface $rateLimitCache,
        private string $endpoint = self::DEFAULT_ENDPOINT,
    ) {
    }

    /**
     * @param list<array<string, bool|int|string>> $containers
     * @param list<array<string, bool|int|string>> $items
     */
    public function sendPackRequest(array $containers, array $items): ShipmonkSampleApiResponseDTO
    {
        if ($this->isRateLimitExceeded()) {
            throw new PackingProviderException('The Shipmonk sample packing API is rate limited.');
        }

        try {
            $body = json_encode(['containers' => $containers, 'items' => $items], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidInputException('Packing request cannot be encoded as JSON.', previous: $exception);
        }

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'body' => $body,
                'headers' => ['Content-Type' => 'application/json'],
            ]);
        } catch (\Throwable $exception) {
            throw new PackingProviderException(
                'The Shipmonk sample packing API is unavailable.',
                previous: $exception,
            );
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 429) {
            try {
                $retryAfterSeconds = (int) $response->getHeaderLine('Retry-After');
                $this->rateLimitCache->get(
                    self::RATE_LIMIT_EXCEEDED_CACHE_KEY,
                    static function (CacheItemInterface $item) use ($retryAfterSeconds): bool {
                        $item->expiresAfter($retryAfterSeconds);

                        return true;
                    },
                );
            } catch (\Throwable) {
            }

            throw new PackingProviderException('The Shipmonk sample packing API is rate limited.');
        }

        return match (true) {
            $statusCode >= 500 =>
                throw new PackingProviderException('The Shipmonk sample packing API is unavailable.'),
            $statusCode === 400, $statusCode === 422 =>
                throw new InvalidInputException('The Shipmonk sample packing API rejected the packing request.'),
            $statusCode < 200, $statusCode >= 300 =>
                throw new PackingProviderException('The Shipmonk sample packing API is unavailable.'),
            default => $this->parseResponse((string) $response->getBody()),
        };
    }

    private function isRateLimitExceeded(): bool
    {
        try {
            return $this->rateLimitCache->get(
                self::RATE_LIMIT_EXCEEDED_CACHE_KEY,
                static function (CacheItemInterface $_item, bool &$save): bool {
                    $save = false;

                    return false;
                },
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function parseResponse(string $body): ShipmonkSampleApiResponseDTO
    {
        try {
            $response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PackingProviderException(
                'The Shipmonk sample packing API returned invalid JSON.',
                previous: $exception,
            );
        }

        if (!is_array($response) || array_is_list($response)) {
            throw new PackingProviderException(
                'The Shipmonk sample packing API returned an invalid response.',
            );
        }

        /** @var array<string, mixed> $response */
        return ShipmonkSampleApiResponseDTO::fromArray($response);
    }
}
