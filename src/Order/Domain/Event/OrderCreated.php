<?php

namespace App\Order\Domain\Event;

use App\Cart\Domain\CartId;
use App\Cart\Domain\Money;
use App\Order\Domain\OrderId;

final class OrderCreated
{
    private OrderId $orderId;
    private CartId $cartId;
    private Money $total;

    public function __construct(OrderId $orderId, CartId $cartId, Money $total)
    {
        $this->orderId = $orderId;
        $this->cartId = $cartId;
        $this->total = $total;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function cartId(): CartId
    {
        return $this->cartId;
    }

    public function total(): Money
    {
        return $this->total;
    }
}
