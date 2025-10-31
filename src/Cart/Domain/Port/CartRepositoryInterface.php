<?php

namespace App\Cart\Domain\Port;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartId;

interface CartRepositoryInterface
{
    public function save(Cart $cart): void;

    public function get(CartId $id): ?Cart;

    public function findByUserId(string $userId): ?Cart;
}
