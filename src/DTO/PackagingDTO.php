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

    public static function fromEntity(Packaging $packaging): self
    {
        return new self(
            id: $packaging->getId(),
            width: $packaging->getWidth(),
            height: $packaging->getHeight(),
            length: $packaging->getLength(),
            maxWeight: $packaging->getMaxWeight(),
        );
    }
}
