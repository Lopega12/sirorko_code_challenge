<?php

namespace App\Order\Domain;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function get(OrderId $id): ?Order;
}
