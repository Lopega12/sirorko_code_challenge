<?php

namespace App\Tests\Order\Integration;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartId;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use App\Order\Application\Handler\CancelOrderHandler;
use App\Order\Application\Command\CancelOrderCommand;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use App\Order\Domain\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CancelOrderTest extends KernelTestCase
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private CancelOrderHandler $cancelHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->orderRepository = $container->get(OrderRepositoryInterface::class);
        $this->cartRepository = $container->get(CartRepositoryInterface::class);
        $this->cancelHandler = $container->get(CancelOrderHandler::class);
    }

    public function testCanCancelPendingOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);
        $this->orderRepository->save($order);

        // Act
        $command = new CancelOrderCommand((string) $orderId, $userId);
        ($this->cancelHandler)($command);

        // Assert
        $updatedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($updatedOrder);
        $this->assertTrue($updatedOrder->status()->isCancelled());
    }

    public function testCanCancelPaymentFailedOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAYMENT_FAILED);
        $this->orderRepository->save($order);

        // Act
        $command = new CancelOrderCommand((string) $orderId, $userId);
        ($this->cancelHandler)($command);

        // Assert
        $updatedOrder = $this->orderRepository->get($orderId);
        $this->assertNotNull($updatedOrder);
        $this->assertTrue($updatedOrder->status()->isCancelled());
    }

    public function testCannotCancelPaidOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAID);
        $this->orderRepository->save($order);

        // Act & Assert
        $this->expectException(\DomainException::class);
        $command = new CancelOrderCommand((string) $orderId, $userId);
        ($this->cancelHandler)($command);
    }

    public function testCannotCancelProcessingOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PROCESSING);
        $this->orderRepository->save($order);

        // Act & Assert
        $this->expectException(\DomainException::class);
        $command = new CancelOrderCommand((string) $orderId, $userId);
        ($this->cancelHandler)($command);
    }

    public function testCannotCancelCompletedOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::COMPLETED);
        $this->orderRepository->save($order);

        // Act & Assert
        $this->expectException(\DomainException::class);
        $command = new CancelOrderCommand((string) $orderId, $userId);
        ($this->cancelHandler)($command);
    }

    public function testCancelNonExistentOrder(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $command = new CancelOrderCommand('non-existent-id', 'user-123');
        ($this->cancelHandler)($command);
    }

    private function createCartWithItems(string $userId): Cart
    {
        $cart = Cart::createForUser($userId);

        $item1 = new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(99.99),
            2
        );

        $item2 = new CartItem(
            ProductId::fromString('prod-2'),
            'Product 2',
            Money::fromFloat(49.99),
            1
        );

        $cart->addItem($item1);
        $cart->addItem($item2);

        return $cart;
    }
}

