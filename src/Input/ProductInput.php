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
        if ($width <= 0) {
            throw new InvalidInput('Width must be greater than 0');
        }

        if ($height <= 0) {
            throw new InvalidInput('Height must be greater than 0');
        }

        if ($length <= 0) {
            throw new InvalidInput('Length must be greater than 0');
        }

        if ($weight <= 0) {
            throw new InvalidInput('Weight must be greater than 0');
        }
    }

    /** @param array{width: mixed, height: mixed, length: mixed, weight: mixed} $input */
    public static function fromArray(array $input): self
    {
        return new self(
            width: self::floatField($input, 'width'),
            height: self::floatField($input, 'height'),
            length: self::floatField($input, 'length'),
            weight: self::floatField($input, 'weight'),
        );
    }

    /** @param array<string, mixed> $input */
    private static function floatField(array $input, string $field): float
    {
        if (!array_key_exists($field, $input)) {
            throw new InvalidInput(sprintf('Missing value at index `%s`', $field));
        }

        $value = $input[$field];
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidInput(sprintf('Value at index `%s` must be a number.', $field));
        }

        return (float) $value;
    }
}
