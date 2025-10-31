<?php

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;

final class InMemoryProductRepository implements ProductRepositoryInterface
{
    /** @var array<string, Product> */
    private array $storage = [];

    public function save(Product $product): void
    {
        $this->storage[$product->getId()] = $product;
    }

    public function find(string $id): ?Product
    {
        return $this->storage[$id] ?? null;
    }

    public function findBySku(string $sku): ?Product
    {
        foreach ($this->storage as $p) {
            if ($p->getSku() === $sku) {
                return $p;
            }
        }

        return null;
    }

    public function remove(Product $product): void
    {
        unset($this->storage[$product->getId()]);
    }

    public function list(int $limit = 50, int $offset = 0): array
    {
        return array_slice(array_values($this->storage), $offset, $limit);
    }
}
