<?php

namespace App\Tests\Order\Integration;

use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Handler\AddItemToCartHandler;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Cart\Domain\Cart;
use App\Cart\Domain\CartItem;
use App\Cart\Domain\Money;
use App\Cart\Domain\Port\CartRepositoryInterface;
use App\Cart\Domain\ProductId;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CheckoutFlowTest extends KernelTestCase
{
    private CartRepositoryInterface $cartRepository;
    private OrderRepositoryInterface $orderRepository;
    private CheckoutCartHandler $checkoutHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->cartRepository = $container->get(CartRepositoryInterface::class);
        $this->orderRepository = $container->get(OrderRepositoryInterface::class);
        $this->checkoutHandler = $container->get(CheckoutCartHandler::class);
    }

    public function testCheckoutCreatesOrderWithPendingStatus(): void
    {
        // Arrange
        $userId = 'user-123';
        $cart = $this->createCartWithItems($userId);
        $this->cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $this->assertInstanceOf(OrderId::class, $orderId);

        $order = $this->orderRepository->get($orderId);
        $this->assertNotNull($order);
        $this->assertTrue($order->status()->isPending());
        $this->assertEquals($cart->id()->toString(), $order->cartId()->toString());
        $this->assertCount(2, $order->items());
        $this->assertEquals(249.97, $order->total()->toFloat());
    }

    public function testCheckoutWithEmptyCartFails(): void
    {
        // Arrange
        $userId = 'user-without-cart';

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cart not found');

        $command = new CheckoutCartCommand($userId);
        ($this->checkoutHandler)($command);
    }

    public function testCheckoutCalculatesCorrectTotal(): void
    {
        // Arrange
        $userId = 'user-456';
        $cart = Cart::createForUser($userId);

        // Add items with different quantities and prices
        $cart->addItem(new CartItem(
            ProductId::fromString('prod-1'),
            'Product 1',
            Money::fromFloat(10.00),
            3
        ));

        $cart->addItem(new CartItem(
            ProductId::fromString('prod-2'),
            'Product 2',
            Money::fromFloat(25.50),
            2
        ));

        $this->cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $order = $this->orderRepository->get($orderId);
        $this->assertNotNull($order);

        // 3 * 10.00 + 2 * 25.50 = 30.00 + 51.00 = 81.00
        $this->assertEquals(81.00, $order->total()->toFloat());
    }

    public function testCheckoutPreservesCartItems(): void
    {
        // Arrange
        $userId = 'user-789';
        $cart = Cart::createForUser($userId);

        $item1 = new CartItem(
            ProductId::fromString('prod-alpha'),
            'Alpha Product',
            Money::fromFloat(15.99),
            1
        );

        $item2 = new CartItem(
            ProductId::fromString('prod-beta'),
            'Beta Product',
            Money::fromFloat(32.50),
            2
        );

        $cart->addItem($item1);
        $cart->addItem($item2);
        $this->cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $order = $this->orderRepository->get($orderId);
        $this->assertNotNull($order);
        $this->assertCount(2, $order->items());

        $orderItems = $order->items();
        $this->assertEquals('Alpha Product', $orderItems[0]->name());
        $this->assertEquals(15.99, $orderItems[0]->price()->toFloat());
        $this->assertEquals(1, $orderItems[0]->quantity());

        $this->assertEquals('Beta Product', $orderItems[1]->name());
        $this->assertEquals(32.50, $orderItems[1]->price()->toFloat());
        $this->assertEquals(2, $orderItems[1]->quantity());
    }

    public function testMultipleCheckoutsCreateSeparateOrders(): void
    {
        // Arrange
        $userId1 = 'user-001';
        $userId2 = 'user-002';

        $cart1 = $this->createCartWithItems($userId1);
        $cart2 = $this->createCartWithItems($userId2);

        $this->cartRepository->save($cart1);
        $this->cartRepository->save($cart2);

        // Act
        $orderId1 = ($this->checkoutHandler)(new CheckoutCartCommand($userId1));
        $orderId2 = ($this->checkoutHandler)(new CheckoutCartCommand($userId2));

        // Assert
        $this->assertNotEquals((string) $orderId1, (string) $orderId2);

        $order1 = $this->orderRepository->get($orderId1);
        $order2 = $this->orderRepository->get($orderId2);

        $this->assertNotNull($order1);
        $this->assertNotNull($order2);
        $this->assertNotEquals($order1->cartId()->toString(), $order2->cartId()->toString());
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

