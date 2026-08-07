<?php

declare(strict_types=1);

namespace App\Output;

use App\Service\PackingService\PackingResult;

final readonly class SuccessOutput extends AbstractOutput
{
    public function __construct(private PackingResult $packingResult)
    {
    }

    /** @return array{box: array{width: float, height: float, length: float}} */
    protected function data(): array
    {
        return [
            'box' => [
                'width' => $this->packingResult->width,
                'height' => $this->packingResult->height,
                'length' => $this->packingResult->length,
            ],
        ];
    }

    protected function error(): ?array
    {
        return null;
    }
}
