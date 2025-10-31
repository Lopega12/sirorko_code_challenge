<?php

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Message\Order\OrderProcessCartMessage;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class CheckoutCartHandler
{
    private CartRepositoryInterface $cartRepository;
    private OrderRepositoryInterface $orderRepository;
    private MessageBusInterface $bus;

    public function __construct(CartRepositoryInterface $cartRepository, OrderRepositoryInterface $orderRepository, MessageBusInterface $bus)
    {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->bus = $bus;
    }

    public function __invoke(CheckoutCartCommand $command): OrderId
    {
        $cart = $this->cartRepository->findByUserId($command->userId);
        if (!$cart) {
            throw new \InvalidArgumentException('Cart not found');
        }

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());
        $this->orderRepository->save($order);

        // Dispatch a message to messenger bus for async processing
        $this->bus->dispatch(new OrderProcessCartMessage((string) $orderId));

        return $orderId;
    }
}
