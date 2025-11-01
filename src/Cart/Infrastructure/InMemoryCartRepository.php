<?php

namespace App\Cart\Infrastructure;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartId;
use App\Cart\Domain\Port\CartRepositoryInterface;

final class InMemoryCartRepository implements CartRepositoryInterface
{
    /** @var array<string, Cart> */
    private array $storage = [];

    public function save(Cart $cart): void
    {
        $this->storage[(string) $cart->id()] = $cart;
    }

    public function get(CartId $id): ?Cart
    {
        return $this->storage[(string) $id] ?? null;
    }

    public function findByUserId(string $userId): ?Cart
    {
        foreach ($this->storage as $cart) {
            // assume Cart has method userId() or getOwnerId.; if not, we will add
            if (method_exists($cart, 'userId') && $cart->userId() === $userId) {
                return $cart;
            }
        }

        return null;
    }
}
