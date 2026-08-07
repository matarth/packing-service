<?php

declare(strict_types=1);

namespace App\Output;

use JsonSerializable;

abstract readonly class AbstractOutput implements JsonSerializable
{
    /** @return array{data: mixed, error: null|array{code: string, message: string}} */
    final public function jsonSerialize(): array
    {
        return [
            'data' => $this->data(),
            'error' => $this->error(),
        ];
    }

    abstract protected function data(): mixed;

    /** @return null|array{code: string, message: string} */
    abstract protected function error(): ?array;
}
