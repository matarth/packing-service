<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\ValueObject\PackingResult;

class LocalPackingService implements PackingServiceInterface
{
    public function findSmallestBox(PackingRequestDTO $request): PackingResult
    {
        return new PackingResult('ddd');
    }
}
