<?php

namespace App\Order\Application\Command;

final class CancelOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
    ) {
    }
}
