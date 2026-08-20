<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\DTO\PackagingDTO;
use App\Tests\DatabaseTestCase;

final class PackagingDTOTest extends DatabaseTestCase
{
    public function testCreatesCanonicalDimensionOrientationFromAnEntity(): void
    {
        $packaging = $this->packagingBuilder
            ->withWidth(3.0)
            ->withHeight(1.0)
            ->withLength(2.0)
            ->withMaxWeight(10.0)
            ->build();

        $dto = PackagingDTO::withNormalizedRotation($packaging);

        self::assertSame(1, $dto->id);
        self::assertSame(1.0, $dto->width);
        self::assertSame(2.0, $dto->height);
        self::assertSame(3.0, $dto->length);
        self::assertSame(10.0, $dto->maxWeight);
        self::assertSame(1.0, $packaging->getSmallEdge());
        self::assertSame(2.0, $packaging->getMiddleEdge());
        self::assertSame(3.0, $packaging->getLongEdge());
    }
}
