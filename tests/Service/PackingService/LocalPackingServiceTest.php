<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\DTO\PackagingDTO;
use App\DTO\PackingRequestDTO;
use App\Exception\NoPackagingFitsException;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Service\PackingService\LocalPackingService;
use PHPUnit\Framework\TestCase;

final class LocalPackingServiceTest extends TestCase
{
    public function testSelectsTheSmallestPackagingThatFitsProductsInOneRow(): void
    {
        $result = $this->service()->findSmallestBox($this->request(
            [
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 1.0),
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 1.0),
            ],
            [
                new PackagingDTO(id: 2, width: 2.0, height: 2.0, length: 7.0, maxWeight: 10.0),
                new PackagingDTO(id: 1, width: 2.0, height: 2.0, length: 6.0, maxWeight: 10.0),
            ],
        ));

        self::assertSame('1', $result->containerId);
    }

    public function testRotatesProductsBeforeCheckingTheirDimensions(): void
    {
        $result = $this->service()->findSmallestBox($this->request(
            [new ProductInput(width: 3.0, height: 1.0, length: 2.0, weight: 1.0)],
            [new PackagingDTO(id: 1, width: 1.0, height: 2.0, length: 3.0, maxWeight: 10.0)],
        ));

        self::assertSame('1', $result->containerId);
    }

    public function testSkipsPackagingWithInsufficientWeightCapacity(): void
    {
        $result = $this->service()->findSmallestBox($this->request(
            [
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 3.0),
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 3.0),
            ],
            [
                new PackagingDTO(id: 1, width: 2.0, height: 2.0, length: 6.0, maxWeight: 5.0),
                new PackagingDTO(id: 2, width: 2.0, height: 2.0, length: 6.0, maxWeight: 6.0),
            ],
        ));

        self::assertSame('2', $result->containerId);
    }

    public function testThrowsWhenProductsDoNotFitInOneRow(): void
    {
        $this->expectException(NoPackagingFitsException::class);

        $this->service()->findSmallestBox($this->request(
            [
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 1.0),
                new ProductInput(width: 2.0, height: 2.0, length: 3.0, weight: 1.0),
            ],
            [new PackagingDTO(id: 1, width: 2.0, height: 2.0, length: 5.0, maxWeight: 10.0)],
        ));
    }

    /** @param list<ProductInput> $products @param list<PackagingDTO> $packagings */
    private function request(array $products, array $packagings): PackingRequestDTO
    {
        return new PackingRequestDTO(new PackingInput($products), $packagings);
    }

    private function service(): LocalPackingService
    {
        return new LocalPackingService();
    }
}
