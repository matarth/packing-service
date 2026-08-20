<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;
use App\Entity\CachedPackingResult;
use App\Entity\Packaging;
use App\Input\ProductInput;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class CachedPackingServiceDecorator implements PackingServiceInterface
{
    public function __construct(
        private PackingServiceInterface $packingService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        $cacheKey = $this->createCacheKey($request);
        $cachedResult = $this->entityManager->getRepository(CachedPackingResult::class)->findOneBy(
            ['cacheKey' => $cacheKey],
        );
        if ($cachedResult instanceof CachedPackingResult) {
            return new PackingResult((string) $cachedResult->getPackaging()->getId());
        }

        $result = $this->packingService->findSmallestBox($request);
        $packaging = $this->entityManager->find(Packaging::class, $result->containerId);
        if (!$packaging instanceof Packaging) {
            throw new LogicException('The packing service returned a packaging that is not persisted.');
        }

        $this->entityManager->persist(new CachedPackingResult($cacheKey, $packaging));
        $this->entityManager->flush();

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
