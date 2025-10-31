<?php

namespace App\Cart\Domain;

final class Cart
{
    private CartId $id;
    private ?string $userId;
    /** @var CartItem[] */
    private array $items = [];

    public function __construct(CartId $id, ?string $userId = null)
    {
        $this->id = $id;
        $this->userId = $userId;
    }

    public static function createForUser(string $userId): self
    {
        return new self(CartId::generate(), $userId);
    }

    public function id(): CartId
    {
        return $this->id;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function addItem(CartItem $item): void
    {
        foreach ($this->items as $idx => $existing) {
            if ($existing->productId()->equals($item->productId())) {
                $newQuantity = $existing->quantity() + $item->quantity();
                $this->items[$idx] = $existing->withQuantity($newQuantity);

                return;
            }
        }

        $this->items[] = $item;
    }

    public function updateItemQuantity(ProductId $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($productId);

            return;
        }

        foreach ($this->items as $idx => $existing) {
            if ($existing->productId()->equals($productId)) {
                $this->items[$idx] = $existing->withQuantity($quantity);

                return;
            }
        }

        throw new \InvalidArgumentException('Product not found in cart');
    }

    public function removeItem(ProductId $productId): void
    {
        foreach ($this->items as $idx => $existing) {
            if ($existing->productId()->equals($productId)) {
                array_splice($this->items, $idx, 1);

                return;
            }
        }

        throw new \InvalidArgumentException('Product not found in cart');
    }

    public function total(): Money
    {
        $total = Money::fromCents(0);
        foreach ($this->items as $item) {
            $total = $total->add($item->total());
        }

        return $total;
    }
}
