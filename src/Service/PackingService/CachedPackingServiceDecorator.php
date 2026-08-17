<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackingRequestDTO;
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
        return 'packing_result_' . hash('sha256', serialize($request));
    }
}
