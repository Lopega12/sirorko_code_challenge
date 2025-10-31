<?php

namespace App\Order\Application\Handler;

use App\Order\Application\Command\CancelOrderCommand;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

final class CancelOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CancelOrderCommand $command): void
    {
        $orderId = OrderId::fromString($command->orderId);
        $order = $this->orderRepository->get($orderId);

        if (!$order) {
            throw new \InvalidArgumentException('Order not found');
        }

        // TODO: Verificar que la orden pertenece al usuario
        // if ($order->userId() !== $command->userId) {
        //     throw new UnauthorizedOrderAccessException();
        // }

        try {
            $order->cancel();
            $this->orderRepository->save($order);

            $this->logger->info('Order cancelled successfully', [
                'order_id' => $command->orderId,
                'user_id' => $command->userId,
            ]);
        } catch (\DomainException $e) {
            $this->logger->warning('Cannot cancel order', [
                'order_id' => $command->orderId,
                'reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
