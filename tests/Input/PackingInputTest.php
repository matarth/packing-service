<?php

declare(strict_types=1);

namespace App\Tests\Input;

use App\Exception\InvalidInputException;
use App\Input\PackingInput;
use App\Input\ProductInput;
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

    public function testCreatesProductsInCanonicalOrientation(): void
    {
        $product = new ProductInput(width: 3.0, height: 1.0, length: 2.0, weight: 4.0);

        $input = new PackingInput([$product]);

        self::assertSame(1.0, $input->products[0]->width);
        self::assertSame(2.0, $input->products[0]->height);
        self::assertSame(3.0, $input->products[0]->length);
        self::assertSame(4.0, $input->products[0]->weight);
        self::assertSame(3.0, $product->width);
    }

    public function testAcceptsConfiguredInputLimits(): void
    {
        $input = PackingInput::fromArray([
            'products' => array_fill(0, 1000, [
                'width' => 2_147_483.647,
                'height' => 2_147_483.647,
                'length' => 2_147_483.647,
                'weight' => 2_147_483.647,
            ]),
        ]);

        self::assertCount(1000, $input->products);
        self::assertSame(2_147_483.647, $input->products[999]->weight);
    }

    /** @dataProvider invalidInputProvider */
    public function testRejectsInvalidInput(array $input, string $message): void
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage($message);

        PackingInput::fromArray($input);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function invalidInputProvider(): iterable
    {
        yield 'missing products' => [[], 'Field "products" is a required field of type array.'];
        yield 'empty products' => [['products' => []], 'Field "products" is a required field of type array.'];
        yield 'products object instead of list' => [[
            'products' => ['sku-1' => self::validProduct()],
        ], 'Field "products" must be a JSON list.'];
        yield 'too many products' => [[
            'products' => array_fill(0, 1001, self::validProduct()),
        ], 'Field "products" must contain at most 1000 products.'];
        yield 'non-array product' => [['products' => [true]], 'Each product must be an object.'];
        yield 'null product' => [['products' => [null]], 'Each product must be an object.'];
        yield 'product list instead of object' => [[
            'products' => [[1, 2, 3, 4]],
        ], 'Each product must be an object.'];
        yield 'missing product field' => [
            ['products' => [['width' => 1], ['hight' => 1]]], 'Missing value at index `height`'
        ];
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
        yield 'positive infinity' => [['products' => [[
            'width' => INF,
            'height' => 1,
            'length' => 1,
            'weight' => 1,
        ]]], 'Width must be finite'];
        yield 'measurement above upper bound' => [['products' => [[
            'width' => 2_147_483.648,
            'height' => 1,
            'length' => 1,
            'weight' => 1,
        ]]], 'Width must not exceed 2147483.647'];
    }

    /** @return array{width: int, height: int, length: int, weight: int} */
    private static function validProduct(): array
    {
        return ['width' => 1, 'height' => 1, 'length' => 1, 'weight' => 1];
    }
}
