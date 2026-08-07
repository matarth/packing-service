<?php

declare(strict_types=1);

namespace App\Facade;

use App\Input\PackingInput;
use App\Output\SuccessOutput;
use App\Service\PackingService\PackingResult;

final class PackingFacade
{
    public function run(PackingInput $input): SuccessOutput
    {
        return new SuccessOutput(new PackingResult(1.0, 1.0, 1.0));
    }
}
