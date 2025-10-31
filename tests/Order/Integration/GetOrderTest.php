<?php

namespace App\Tests\Order\Integration;

use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use App\Order\Application\Handler\GetOrderHandler;
use App\Order\Application\Query\GetOrderQuery;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use App\Order\Domain\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GetOrderTest extends KernelTestCase
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private GetOrderHandler $getOrderHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->orderRepository = $container->get(OrderRepositoryInterface::class);
        $this->cartRepository = $container->get(CartRepositoryInterface::class);
        $this->getOrderHandler = $container->get(GetOrderHandler::class);
    }

    public function testCanRetrievePendingOrder(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PENDING);
        $this->orderRepository->save($order);

        // Act
        $query = new GetOrderQuery((string) $orderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNotNull($retrievedOrder);
        $this->assertEquals((string) $orderId, (string) $retrievedOrder->id());
        $this->assertTrue($retrievedOrder->status()->isPending());
    }

    public function testCanRetrievePaidOrderWithPaymentReference(): void
    {
        // Arrange
        $userId = 'user-456';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $paymentRef = 'payment_abc123';
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::PAID, $paymentRef);
        $this->orderRepository->save($order);

        // Act
        $query = new GetOrderQuery((string) $orderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNotNull($retrievedOrder);
        $this->assertTrue($retrievedOrder->status()->isPaid());
        $this->assertEquals($paymentRef, $retrievedOrder->paymentReference());
    }

    public function testRetrieveNonExistentOrderReturnsNull(): void
    {
        // Arrange
        $userId = 'user-789';
        $nonExistentOrderId = (string) OrderId::generate();

        // Act
        $query = new GetOrderQuery($nonExistentOrderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNull($retrievedOrder);
    }

    public function testRetrievedOrderContainsAllItems(): void
    {
        // Arrange
        $userId = 'user-abc';
        $cart = Cart::createForUser($userId);

        $item1 = new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(25.50),
            2
        );

        $item2 = new CartItem(
            ProductId::fromString('prod-2'),
            'Product 2',
            Money::fromFloat(10.00),
            3
        );

        $cart->addItem($item1);
        $cart->addItem($item2);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());
        $this->orderRepository->save($order);

        // Act
        $query = new GetOrderQuery((string) $orderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNotNull($retrievedOrder);
        $this->assertCount(2, $retrievedOrder->items());

        $items = $retrievedOrder->items();
        $this->assertEquals('Product 1', $items[0]->name());
        $this->assertEquals(2, $items[0]->quantity());
        $this->assertEquals('Product 2', $items[1]->name());
        $this->assertEquals(3, $items[1]->quantity());
    }

    public function testRetrievedOrderHasCorrectTotal(): void
    {
        // Arrange
        $userId = 'user-def';
        $cart = Cart::createForUser($userId);

        $cart->addItem(new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(50.00),
            2
        ));

        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total());
        $this->orderRepository->save($order);

        // Act
        $query = new GetOrderQuery((string) $orderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNotNull($retrievedOrder);
        $this->assertEquals(100.00, $retrievedOrder->total()->toFloat());
    }

    public function testCanRetrieveCancelledOrder(): void
    {
        // Arrange
        $userId = 'user-ghi';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        $orderId = OrderId::generate();
        $order = new Order($orderId, $cart->id(), $cart->items(), $cart->total(), OrderStatus::CANCELLED);
        $this->orderRepository->save($order);

        // Act
        $query = new GetOrderQuery((string) $orderId, $userId);
        $retrievedOrder = ($this->getOrderHandler)($query);

        // Assert
        $this->assertNotNull($retrievedOrder);
        $this->assertTrue($retrievedOrder->status()->isCancelled());
    }

    private function createCartWithItems(string $userId): Cart
    {
        $cart = Cart::createForUser($userId);

        $item = new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(99.99),
            1
        );

        $cart->addItem($item);

        return $cart;
    }
}

