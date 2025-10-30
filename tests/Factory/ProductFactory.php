<?php

namespace App\Tests\Factory;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\Money;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use function Zenstruck\Foundry\faker;

final class ProductFactory extends PersistentObjectFactory
{
    /**
     * Create the Product instance using the domain constructor so value objects are passed correctly.
     * @param array $attributes
     * @return Product
     */
    protected function instantiate(array $attributes): object
    {
        $sku = $attributes['sku'];
        $name = $attributes['name'];
        $price = $attributes['price'];
        $currency = $attributes['currency'] ?? 'EUR';
        $stock = $attributes['stock'] ?? 0;
        $description = $attributes['description'] ?? null;

        return new Product($sku, $name, $price, $currency,$stock, $description);
    }

    protected function defaults(): array|callable
    {
        $amount = faker()->randomFloat(2, 1, 500);
        return [
            'sku' => faker()->unique()->bothify('sku-####-???'),
            'name' => faker()->words(3, true),
            'price' => $amount, // normalize in instantiate()
            'stock' => faker()->numberBetween(0, 100),
            'description' => faker()->sentence(),
            'currency' => 'EUR',
        ];
    }

    public static function class(): string
    {
        return Product::class;
    }

    protected function initialize(): static
    {
        return $this;
    }
}
