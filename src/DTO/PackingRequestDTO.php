<?php

declare(strict_types=1);

namespace App\DTO;

use App\Input\PackingInput;

final readonly class PackingRequestDTO
{
    /** @param list<PackagingDTO> $packagings */
    public function __construct(
        public PackingInput $packingInput,
        public array $packagings,
    ) {
    }
}
