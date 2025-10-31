<?php

namespace App\Order\Domain;

use App\Cart\Domain\CartId;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;

final class Order
{
    private OrderId $id;
    private CartId $cartId;
    /** @var CartItem[] */
    private array $items;
    private Money $total;

    public function __construct(OrderId $id, CartId $cartId, array $items, Money $total)
    {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->items = $items;
        $this->total = $total;
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function cartId(): CartId
    {
        return $this->cartId;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function total(): Money
    {
        return $this->total;
    }
}
