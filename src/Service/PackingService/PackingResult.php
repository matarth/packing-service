<?php

declare(strict_types=1);

namespace App\Service\PackingService;

final readonly class PackingResult
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
    ) {
    }
}
