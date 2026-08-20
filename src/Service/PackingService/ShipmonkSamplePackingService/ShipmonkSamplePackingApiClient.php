<?php

declare(strict_types=1);

namespace App\Service\PackingService\ShipmonkSamplePackingService;

use App\Exception\InvalidInputException;
use App\Exception\PackingProviderUnavailableException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

final readonly class ShipmonkSamplePackingApiClient
{
    public const DEFAULT_ENDPOINT = 'https://binpacking.janedbal.cz/api/v1/pack';

    public function __construct(
        private ClientInterface $httpClient,
        private string $endpoint = self::DEFAULT_ENDPOINT,
    ) {
    }

    /**
     * @param list<array<string, bool|int|string>> $containers
     * @param list<array<string, bool|int|string>> $items
     */
    public function sendPackRequest(array $containers, array $items): ShipmonkSampleApiResponseDTO
    {
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
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API is unavailable.',
                previous: $exception,
            );
        }

        if ($response->getStatusCode() >= 500 || $response->getStatusCode() === 429) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API is unavailable.',
            );
        }

        if (in_array($response->getStatusCode(), [400, 422], true)) {
            throw new InvalidInputException('The Shipmonk sample packing API rejected the packing request.');
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new PackingProviderUnavailableException('The Shipmonk sample packing API is unavailable.');
        }

        return $this->parseResponse((string) $response->getBody());
    }

    private function parseResponse(string $body): ShipmonkSampleApiResponseDTO
    {
        try {
            $response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API returned invalid JSON.',
                previous: $exception,
            );
        }

        if (!is_array($response) || array_is_list($response)) {
            throw new PackingProviderUnavailableException(
                'The Shipmonk sample packing API returned an invalid response.',
            );
        }

        /** @var array<string, mixed> $response */
        return ShipmonkSampleApiResponseDTO::fromArray($response);
    }
}
