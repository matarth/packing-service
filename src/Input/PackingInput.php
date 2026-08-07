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
        if (!array_key_exists('products', $input)) {
            throw new InvalidInput('Field "products" is required.');
        }

        if (!is_array($input['products']) || !array_is_list($input['products'])) {
            throw new InvalidInput('Field "products" must be an array.');
        }

        $products = [];
        foreach ($input['products'] as $index => $product) {
            if (!is_array($product) || array_is_list($product)) {
                throw new InvalidInput(sprintf('Product at index %d must be an object.', $index));
            }

            /** @var array<string, mixed> $product */
            $products[] = ProductInput::fromArray($product, $index);
        }

        return new self($products);
    }
}
