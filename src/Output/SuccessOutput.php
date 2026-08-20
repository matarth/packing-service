<?php

declare(strict_types=1);

namespace App\Output;

use App\DTO\PackingResult;

final readonly class SuccessOutput extends AbstractOutput
{
    public function __construct(private PackingResult $packingResult)
    {
    }

    /** @return array{box: string} */
    protected function data(): array
    {
        return [
            'box' => $this->packingResult->containerId
        ];
    }

    protected function error(): ?array
    {
        return null;
    }
}
