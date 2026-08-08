<?php

declare(strict_types=1);

namespace App\Service\PackingService;

use App\Exception\PackingProviderUnavailable;
use App\Input\PackingInput;
use App\ValueObject\PackingResult;

interface PackingServiceInterface
{
    /** @throws PackingProviderUnavailable */
    public function findSmallestBox(PackingInput $input): PackingResult;
}
