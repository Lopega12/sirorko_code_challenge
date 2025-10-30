<?php

namespace App\Product\Domain\Repository;

use App\Product\Domain\Entity\Product;

interface ProductRepositoryInterface
{
    public function find(string $id): ?Product;

    public function findBySku(string $sku): ?Product;

    public function save(Product $product): void;

    public function remove(Product $product): void;

    public function list(int $limit = 50, int $offset = 0): array;
}
