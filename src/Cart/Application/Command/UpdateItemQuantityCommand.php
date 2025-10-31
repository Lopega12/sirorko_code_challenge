<?php

namespace App\Cart\Application\Command;

use App\Cart\Domain\ProductId;

final class UpdateItemQuantityCommand
{
    public string $userId;
    public ProductId $productId;
    public int $quantity;

    public function __construct(string $userId, ProductId $productId, int $quantity)
    {
        $this->userId = $userId;
        $this->productId = $productId;
        $this->quantity = $quantity;
    }
}
