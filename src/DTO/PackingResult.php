<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PackingResult
{
    public function __construct(
        public string $containerId,
    ) {
    }
}
