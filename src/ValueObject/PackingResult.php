<?php

declare(strict_types=1);

namespace App\ValueObject;

final readonly class PackingResult
{
    public function __construct(
        public string $containerId,
    ) {
    }
}
