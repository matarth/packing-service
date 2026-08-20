<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\NoPackagingFitsException;
use App\Input\ProductInput;
use App\ValueObject\PackingResult;

class LocalPackingService implements PackingServiceInterface
{
    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        $products = $request->packingInput->products;
        $packagings = $request->packagings;
        usort($packagings, static function (PackagingDTO $left, PackagingDTO $right): int {
            $volumeComparison = self::volume($left) <=> self::volume($right);
            if ($volumeComparison !== 0) {
                return $volumeComparison;
            }

            return ($left->id ?? PHP_INT_MAX) <=> ($right->id ?? PHP_INT_MAX);
        });

        foreach ($packagings as $packaging) {
            if ($packaging->id === null || !$this->canPackInOneRow($products, $packaging)) {
                continue;
            }

            return new PackingResult((string) $packaging->id);
        }

        throw new NoPackagingFitsException('No available packaging can fit the products in one row.');
    }

    /** @param list<ProductInput> $products */
    private function canPackInOneRow(array $products, PackagingDTO $packaging): bool
    {
        $totalWeight = 0.0;
        $usedLongEdge = 0.0;

        foreach ($products as $product) {
            if ($product->width > $packaging->width || $product->height > $packaging->height) {
                return false;
            }

            $totalWeight += $product->weight;
            $usedLongEdge += $product->length;
        }

        return $totalWeight <= $packaging->maxWeight && $usedLongEdge <= $packaging->length;
    }

    private static function volume(PackagingDTO $packaging): float
    {
        return $packaging->width * $packaging->height * $packaging->length;
    }
}
