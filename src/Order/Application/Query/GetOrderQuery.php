<?php

namespace App\Order\Application\Query;

final class GetOrderQuery
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $userId,
    ) {
    }
}
