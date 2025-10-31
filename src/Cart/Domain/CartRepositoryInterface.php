<?php

namespace App\Cart\Domain;

interface CartRepositoryInterface
{
    public function save(Cart $cart): void;

    public function get(CartId $id): ?Cart;

    public function findByUserId(string $userId): ?Cart;
}
