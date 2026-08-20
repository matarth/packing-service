<?php

declare(strict_types=1);

namespace App\Input;

use App\Exception\InvalidInputException;

final readonly class ProductInput
{
    public function __construct(
        public float $width,
        public float $height,
        public float $length,
        public float $weight,
    ) {
        if ($width <= 0) {
            throw new InvalidInputException('Width must be greater than 0');
        }

        if ($height <= 0) {
            throw new InvalidInputException('Height must be greater than 0');
        }

        if ($length <= 0) {
            throw new InvalidInputException('Length must be greater than 0');
        }

        if ($weight <= 0) {
            throw new InvalidInputException('Weight must be greater than 0');
        }
    }

    /** @param array<mixed, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            width: self::floatField($input, 'width'),
            height: self::floatField($input, 'height'),
            length: self::floatField($input, 'length'),
            weight: self::floatField($input, 'weight'),
        );
    }

    /** @param array<mixed, mixed> $input */
    private static function floatField(array $input, string $field): float
    {
        if (!array_key_exists($field, $input)) {
            throw new InvalidInputException(sprintf('Missing value at index `%s`', $field));
        }

        $value = $input[$field];
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidInputException(sprintf('Value at index `%s` must be a number.', $field));
        }

        return (float) $value;
    }
}
