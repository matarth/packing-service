<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Packaging;

final readonly class PackagingDTO
{
    public function __construct(
        public ?int $id,
        public float $width,
        public float $height,
        public float $length,
        public float $maxWeight,
    ) {
    }

    public static function withNormalizedRotation(Packaging $packaging): self
    {
        $dimensions = [
            $packaging->getWidth(),
            $packaging->getHeight(),
            $packaging->getLength(),
        ];
        sort($dimensions, SORT_NUMERIC);

        return new self(
            id: $packaging->getId(),
            width: $dimensions[0],
            height: $dimensions[1],
            length: $dimensions[2],
            maxWeight: $packaging->getMaxWeight(),
        );
    }
}
