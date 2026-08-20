<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Input\ProductInput;
use App\ValueObject\PackingResult;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class CachedPackingServiceDecorator implements PackingServiceInterface
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private PackingServiceInterface $packingService,
        private CacheInterface $cache,
    ) {
    }

    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        /** @var PackingResult $result */
        $result = $this->cache->get(
            $this->createCacheKey($request),
            function (CacheItemInterface $item) use ($request): PackingResult {
                $item->expiresAfter(self::CACHE_TTL_SECONDS);

                return $this->packingService->findSmallestBox($request);
            },
        );

        return $result;
    }

    private function createCacheKey(PackingRequestDTO $request): string
    {
        $products = array_map(
            static fn (ProductInput $product): array => [
                'width' => $product->width,
                'height' => $product->height,
                'length' => $product->length,
                'weight' => $product->weight,
            ],
            $request->packingInput->products,
        );
        usort($products, static fn (array $first, array $second): int => $first <=> $second);

        $packagings = array_map(
            static fn (PackagingDTO $packaging): array => [
                'id' => $packaging->id,
                'width' => $packaging->width,
                'height' => $packaging->height,
                'length' => $packaging->length,
                'maxWeight' => $packaging->maxWeight,
            ],
            $request->packagings,
        );
        usort($packagings, static fn (array $first, array $second): int => $first <=> $second);

        return 'packing_result_' . hash('sha256', serialize([
            'products' => $products,
            'packagings' => $packagings,
        ]));
    }
}
