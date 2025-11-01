<?php

namespace App\Tests\Order\Integration;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use App\Order\Domain\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderStatusTransitionsTest extends KernelTestCase
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->orderRepository = $container->get(OrderRepositoryInterface::class);
        $this->cartRepository = $container->get(CartRepositoryInterface::class);
    }

    public function testOrderStartsAsPending(): void
    {
        // Preparar
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());

        // Verificar
        $this->assertTrue($order->status()->isPending());
        $this->assertEquals(OrderStatus::PENDING, $order->status());
    }

    public function testCanTransitionFromPendingToProcessing(): void
    {
        // Preparar
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());

        // Actuar
        $order->markAsProcessing();
        $this->orderRepository->save($order);

        // Verificar
        $savedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($savedOrder);
        $this->assertTrue($savedOrder->status()->isProcessing());
    }

    public function testCanTransitionFromProcessingToPaid(): void
    {
        // Preparar
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PROCESSING);
        $paymentRef = 'payment_123';
        $order->markAsPaid($paymentRef);
        $this->orderRepository->save($order);

        // Verificar
        $savedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($savedOrder);
        $this->assertTrue($savedOrder->status()->isPaid());
        $this->assertEquals($paymentRef, $savedOrder->paymentReference());
    }

    public function testCanTransitionFromProcessingToPaymentFailed(): void
    {
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PROCESSING);

        $order->markAsPaymentFailed();
        $this->orderRepository->save($order);

        $savedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($savedOrder);
        $this->assertTrue($savedOrder->status()->hasPaymentFailed());
    }

    public function testCanTransitionFromPaidToCompleted(): void
    {
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAID, 'payment_123');

        $order->markAsCompleted();
        $this->orderRepository->save($order);

        $savedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($savedOrder);
        $this->assertTrue($savedOrder->status()->isCompleted());
    }

    public function testCannotMarkAsPaidFromPending(): void
    {

        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);

        $this->expectException(\DomainException::class);
        $order->markAsPaid('payment_123');
    }

    public function testCannotMarkAsPaymentFailedFromPending(): void
    {

        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);


        $this->expectException(\DomainException::class);
        $order->markAsPaymentFailed();
    }

    public function testCannotMarkAsCompletedFromPending(): void
    {
        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);

        $this->expectException(\DomainException::class);
        $order->markAsCompleted();
    }

    public function testCannotProcessOrderTwice(): void
    {

        $cart = $this->createCartWithItems('user-123');
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PROCESSING);


        $this->expectException(\DomainException::class);
        $order->markAsProcessing();
    }

    private function createCartWithItems(string $userId): Cart
    {
        $cart = Cart::createForUser($userId);

        $item = new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(99.99),
            2
        );

        $cart->addItem($item);

        return $cart;
    }
}


