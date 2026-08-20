<?php

declare(strict_types=1);

namespace App\Input;

use App\Exception\InvalidInputException;

final readonly class PackingInput
{
    /** @var list<ProductInput> */
    public array $products;

    /** @param list<ProductInput> $products */
    public function __construct(array $products)
    {
        if ($products === []) {
            throw new InvalidInputException('Field "products" must contain at least one product.');
        }

        $this->products = array_map(ProductInput::withCanonicalOrientation(...), $products);
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        if (
            !array_key_exists('products', $input) ||
            !is_array($input['products']) ||
            [] === $input['products']
        ) {
            throw new InvalidInputException('Field "products" is a required field of type array.');
        }

        $products = [];
        foreach ($input['products'] as $product) {
            if (!is_array($product)) {
                throw new InvalidInputException('Each product must be an object.');
            }

            $products[] = ProductInput::fromArray($product);
        }

        return new self($products);
    }
}
