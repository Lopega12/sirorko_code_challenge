<?php

namespace App\Order\Infrastructure;

use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;

final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<string, Order> */
    private array $storage = [];

    public function save(Order $order): void
    {
        $this->storage[(string) $order->id()] = $order;
    }

    public function get(OrderId $id): ?Order
    {
        return $this->storage[(string) $id] ?? null;
    }
}
