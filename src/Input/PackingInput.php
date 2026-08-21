<?php

declare(strict_types=1);

namespace App\Input;

use App\Exception\InvalidInputException;

final readonly class PackingInput
{
    public const MAX_PRODUCTS = 1000;

    /** @var list<ProductInput> */
    public array $products;

    /** @param array<array-key, ProductInput> $products */
    public function __construct(array $products)
    {
        if ($products === []) {
            throw new InvalidInputException('Field "products" must contain at least one product.');
        }

        if (!array_is_list($products)) {
            throw new InvalidInputException('Field "products" must be a JSON list.');
        }

        if (count($products) > self::MAX_PRODUCTS) {
            throw new InvalidInputException(sprintf(
                'Field "products" must contain at most %d products.',
                self::MAX_PRODUCTS,
            ));
        }

        $this->products = array_map(ProductInput::withCanonicalOrientation(...), $products);
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        if (!array_key_exists('products', $input) || !is_array($input['products']) || [] === $input['products']) {
            throw new InvalidInputException('Field "products" is a required field of type array.');
        }

        if (!array_is_list($input['products'])) {
            throw new InvalidInputException('Field "products" must be a JSON list.');
        }

        if (count($input['products']) > self::MAX_PRODUCTS) {
            throw new InvalidInputException(sprintf(
                'Field "products" must contain at most %d products.',
                self::MAX_PRODUCTS,
            ));
        }

        $products = [];
        foreach ($input['products'] as $product) {
            if (!is_array($product) || array_is_list($product)) {
                throw new InvalidInputException('Each product must be an object.');
            }

            $products[] = ProductInput::fromArray($product);
        }

        return new self($products);
    }
}
