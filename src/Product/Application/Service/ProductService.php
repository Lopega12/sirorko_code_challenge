<?php

namespace App\Product\Application\Service;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\Money;

final class ProductService
{
    private ProductRepositoryInterface $repo;

    public function __construct(ProductRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function create(string $sku, string $name, float $price, string $currency, int $stock = 0, ?string $description = null): Product
    {
        $money = new Money($price, $currency);
        $product = new Product($sku, $name, $money, $stock, $description);
        $this->repo->save($product);

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['name'])) {
            $product->setName((string) $data['name']);
        }

        if (array_key_exists('description', $data)) {
            $product->setDescription($data['description'] ?? null);
        }

        if (isset($data['price']) && isset($data['currency'])) {
            $product->setPrice(new Money((float) $data['price'], (string) $data['currency']));
        }

        if (isset($data['stock'])) {
            $product->adjustStock((int) $data['stock'] - $product->getStock());
        }

        $this->repo->save($product);

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->repo->remove($product);
    }

    public function get(string $id): ?Product
    {
        return $this->repo->find($id);
    }

    public function list(int $limit = 50, int $offset = 0): array
    {
        return $this->repo->list($limit, $offset);
    }
}
