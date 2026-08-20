<?php

declare(strict_types=1);

namespace App\Logging;

use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

final class ErrorLogLogger extends AbstractLogger
{
    /** @param array<string, mixed> $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $record = [
            'level' => is_string($level) ? $level : get_debug_type($level),
            'message' => (string) $message,
            'context' => array_map(self::normalizeContextValue(...), $context),
        ];
        $encodedRecord = json_encode(
            $record,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE,
        );

        error_log($encodedRecord === false ? '[warning] Unable to encode log record.' : $encodedRecord);
    }

    private static function normalizeContextValue(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'type' => $value::class,
                'message' => $value->getMessage(),
            ];
        }

        if (is_scalar($value) || $value === null || is_array($value)) {
            return $value;
        }

        return get_debug_type($value);
    }
}
