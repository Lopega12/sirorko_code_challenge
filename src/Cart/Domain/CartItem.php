<?php

namespace App\Cart\Domain;

final class CartItem
{
    private ProductId $productId;
    private string $name;
    private Money $price;
    private int $quantity;

    public function __construct(ProductId $productId, string $name, Money $price, int $quantity)
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }

        $this->productId = $productId;
        $this->name = $name;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function withQuantity(int $quantity): self
    {
        return new self($this->productId, $this->name, $this->price, $quantity);
    }

    public function total(): Money
    {
        return $this->price->multiply($this->quantity);
    }
}
