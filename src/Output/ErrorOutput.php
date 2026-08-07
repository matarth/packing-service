<?php

declare(strict_types=1);

namespace App\Output;

final readonly class ErrorOutput extends AbstractOutput
{
    public function __construct(private string $code, private string $message)
    {
    }

    protected function data(): mixed
    {
        return null;
    }

    /** @return array{code: string, message: string} */
    protected function error(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
