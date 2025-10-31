<?php

namespace App\Order\Application\Handler;

use App\Order\Application\Query\GetOrderQuery;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;

final class GetOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function __invoke(GetOrderQuery $query): ?Order
    {
        $orderId = OrderId::fromString($query->orderId);
        $order = $this->orderRepository->get($orderId);

        // TODO: Verificar que la orden pertenece al usuario
        // if ($order && $order->userId() !== $query->userId) {
        //     throw new UnauthorizedOrderAccessException();
        // }

        return $order;
    }
}
