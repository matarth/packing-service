<?php

declare(strict_types=1);

namespace App\Input;

use App\Exception\InvalidInput;

final readonly class ProductInput
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
        public float $weight,
    ) {
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input, int $index): self
    {
        return new self(
            self::positiveNumberField($input, 'width', $index),
            self::positiveNumberField($input, 'height', $index),
            self::positiveNumberField($input, 'length', $index),
            self::positiveNumberField($input, 'weight', $index),
        );
    }

    /** @param array<string, mixed> $input */
    private static function positiveNumberField(array $input, string $field, int $index): float
    {
        if (!array_key_exists($field, $input)) {
            throw new InvalidInput(sprintf('Product at index %d is missing field "%s".', $index, $field));
        }

        if (!is_int($input[$field]) && !is_float($input[$field])) {
            throw new InvalidInput(sprintf('Product at index %d field "%s" must be a number.', $index, $field));
        }

        $value = (float) $input[$field];
        if ($value <= 0.0) {
            throw new InvalidInput(sprintf('Product at index %d field "%s" must be greater than zero.', $index, $field));
        }

        return $value;
    }
}
