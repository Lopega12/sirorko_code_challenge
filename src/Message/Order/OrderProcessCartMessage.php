<?php

namespace App\Message\Order;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'async')]
final class OrderProcessCartMessage
{
    private string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }
}
