<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\DTO\PackingRequestDTO;
use App\DTO\PackingResult;

interface PackingServiceInterface
{
    public function findSmallestBox(PackingRequestDTO $request): PackingResult;
}
