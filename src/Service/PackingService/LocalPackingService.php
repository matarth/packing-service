<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\Input\PackingInput;
use App\ValueObject\PackingResult;

class LocalPackingService implements PackingServiceInterface
{
    public function findSmallestBox(PackingInput $input): PackingResult
    {
        return new PackingResult('ddd');
    }
}
