<?php

namespace App\Cart\Application\Command;

use App\Cart\Domain\ProductId;

final class RemoveItemFromCartCommand
{
    public string $userId;
    public ProductId $productId;

    public function __construct(string $userId, ProductId $productId)
    {
        $this->userId = $userId;
        $this->productId = $productId;
    }
}
