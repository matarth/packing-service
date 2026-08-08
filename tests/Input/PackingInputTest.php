<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Exception\InvalidInput;
use App\Input\PackingInput;
use PHPUnit\Framework\TestCase;

final class PackingInputTest extends TestCase
{
    public function testCreatesTypedProducts(): void
    {
        $input = PackingInput::fromArray([
            'products' => [[
                'width' => 1.5,
                'height' => 2,
                'length' => 3.5,
                'weight' => 4,
            ]],
        ]);

        self::assertSame(1.5, $input->products[0]->width);
        self::assertSame(2.0, $input->products[0]->height);
        self::assertSame(3.5, $input->products[0]->length);
        self::assertSame(4.0, $input->products[0]->weight);
    }

    /** @dataProvider invalidInputProvider */
    public function testRejectsInvalidInput(array $input, string $message): void
    {
        $this->expectException(InvalidInput::class);
        $this->expectExceptionMessage($message);

        PackingInput::fromArray($input);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidInputProvider(): iterable
    {
        yield 'missing products' => [[], 'Field "products" is a required field of type array.'];
        yield 'empty products' => [['products' => []], 'Field "products" is a required field of type array.'];
        yield 'missing product field' => [['products' => [['width' => 1], ['hight' => 1]]], 'Missing value at index `height`'];
        yield 'non-positive dimension' => [['products' => [[
            'width' => 0,
            'height' => 2,
            'length' => 3,
            'weight' => 4,
        ]]], 'Width must be greater than 0'];
        yield 'invalid_type string' => [['products' => [[
            'width' => '1',
            'height' => 1,
            'length' => 1,
            'weight' => 1,
        ]]], 'Value at index `width` must be a number.'];
        yield 'invalid_type null' => [['products' => [[
            'width' => null,
            'height' => 1,
            'length' => 1,
            'weight' => 1,
        ]]], 'Value at index `width` must be a number.'];
    }
}
