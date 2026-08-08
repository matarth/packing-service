<?php

declare(strict_types=1);

namespace App\Input;

use App\Exception\InvalidInput;

final readonly class PackingInput
{
    /** @param list<ProductInput> $products */
    public function __construct(public array $products)
    {
        if ($products === []) {
            throw new InvalidInput('Field "products" must contain at least one product.');
        }
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        if (
            !array_key_exists('products', $input) ||
            !is_array($input['products']) ||
            [] === $input['products']
        ) {
            throw new InvalidInput('Field "products" is a required field of type array.');
        }

        $products = [];
        foreach ($input['products'] as $product) {

            /** @var array{width: float, height: float, length: float, weight: float} $product */
            $products[] = ProductInput::fromArray($product);
        }

        return new self($products);
    }
}
