<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\PackagingDTO;
use App\Entity\Packaging;
use PHPUnit\Framework\TestCase;

final class PackagingDTOTest extends TestCase
{
    public function testCreatesCanonicalDimensionOrientationFromAnEntity(): void
    {
        $packaging = new Packaging(width: 3.0, height: 1.0, length: 2.0, maxWeight: 10.0);

        $dto = PackagingDTO::withNormalizedRotation($packaging);

        self::assertSame(1.0, $dto->width);
        self::assertSame(2.0, $dto->height);
        self::assertSame(3.0, $dto->length);
        self::assertSame(10.0, $dto->maxWeight);
        self::assertSame(1.0, $packaging->getSmallEdge());
        self::assertSame(2.0, $packaging->getMiddleEdge());
        self::assertSame(3.0, $packaging->getLongEdge());
    }
}
