<?php

namespace App\Cart\Application\Handler;

use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Domain\CartRepositoryInterface;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;

final class CheckoutCartHandler
{
    private CartRepositoryInterface $cartRepository;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(CartRepositoryInterface $cartRepository, OrderRepositoryInterface $orderRepository)
    {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
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

        return $orderId;
    }
}
