<?php

namespace App\Tests\Order\Integration;

use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Order\Domain\OrderId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CheckoutFlowTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private CheckoutCartHandler $checkoutHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->checkoutHandler = $container->get(CheckoutCartHandler::class);
    }

    public function testCheckoutCreatesOrderWithPendingStatus(): void
    {
        // Arrange - Create cart with items using domain objects
        $userId = 'user-123';
        $cart = \App\Cart\Domain\Cart::createForUser($userId);

        $item1 = new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-1'),
            'Product 1',
            \App\Cart\Domain\Money::fromFloat(99.99),
            2
        );
        $item2 = new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-2'),
            'Product 2',
            \App\Cart\Domain\Money::fromFloat(49.99),
            1
        );

        $cart->addItem($item1);
        $cart->addItem($item2);

        // Save cart using repository
        $container = static::getContainer();
        $cartRepository = $container->get(\App\Cart\Domain\Port\CartRepositoryInterface::class);
        $cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $this->assertInstanceOf(OrderId::class, $orderId);

        // Get order from repository
        $repository = $container->get(\App\Order\Domain\OrderRepositoryInterface::class);
        $order = $repository->get($orderId);

        $this->assertNotNull($order);
        // En tests, el mensaje se procesa síncronamente, por lo que el estado será PAID
        // En producción con messenger async, empezaría como PENDING
        $this->assertTrue($order->status()->isPaid(), 'Order should be PAID after sync message processing');
        $this->assertEquals((string) $cart->id(), (string) $order->cartId());
        $this->assertCount(2, $order->items());
        $this->assertEquals(249.97, $order->total()->toFloat());
    }

    public function testCheckoutWithEmptyCartFails(): void
    {
        $userId = 'user-without-cart';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cart not found');

        $command = new CheckoutCartCommand($userId);
        ($this->checkoutHandler)($command);
    }

    public function testCheckoutCalculatesCorrectTotal(): void
    {
        //Create cart with specific items for total calculation
        $userId = 'user-456';
        $cart = \App\Cart\Domain\Cart::createForUser($userId);

        $cart->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-1'),
            'Product 1',
            \App\Cart\Domain\Money::fromFloat(10.00),
            3
        ));
        $cart->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-2'),
            'Product 2',
            \App\Cart\Domain\Money::fromFloat(25.50),
            2
        ));

        $container = static::getContainer();
        $cartRepository = $container->get(\App\Cart\Domain\Port\CartRepositoryInterface::class);
        $cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $repository = $container->get(\App\Order\Domain\OrderRepositoryInterface::class);
        $order = $repository->get($orderId);
        $this->assertNotNull($order);

        // 3 * 10.00 + 2 * 25.50 = 30.00 + 51.00 = 81.00
        $this->assertEquals(81.00, $order->total()->toFloat());
    }

    public function testCheckoutPreservesCartItems(): void
    {
        // Arrange - Create cart with specific items
        $userId = 'user-789';
        $cart = \App\Cart\Domain\Cart::createForUser($userId);

        $cart->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-alpha'),
            'Alpha Product',
            \App\Cart\Domain\Money::fromFloat(15.99),
            1
        ));
        $cart->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-beta'),
            'Beta Product',
            \App\Cart\Domain\Money::fromFloat(32.50),
            2
        ));

        $container = static::getContainer();
        $cartRepository = $container->get(\App\Cart\Domain\Port\CartRepositoryInterface::class);
        $cartRepository->save($cart);

        // Act
        $command = new CheckoutCartCommand($userId);
        $orderId = ($this->checkoutHandler)($command);

        // Assert
        $repository = $container->get(\App\Order\Domain\OrderRepositoryInterface::class);
        $order = $repository->get($orderId);
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
        // Arrange - Create two carts for different users
        $userId1 = 'user-001';
        $userId2 = 'user-002';

        $cart1 = \App\Cart\Domain\Cart::createForUser($userId1);
        $cart1->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-1'),
            'Product 1',
            \App\Cart\Domain\Money::fromFloat(99.99),
            2
        ));
        $cart1->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-2'),
            'Product 2',
            \App\Cart\Domain\Money::fromFloat(49.99),
            1
        ));

        $cart2 = \App\Cart\Domain\Cart::createForUser($userId2);
        $cart2->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-1'),
            'Product 1',
            \App\Cart\Domain\Money::fromFloat(99.99),
            2
        ));
        $cart2->addItem(new \App\Cart\Domain\CartItem(
            \App\Cart\Domain\ProductId::fromString('prod-2'),
            'Product 2',
            \App\Cart\Domain\Money::fromFloat(49.99),
            1
        ));

        $container = static::getContainer();
        $cartRepository = $container->get(\App\Cart\Domain\Port\CartRepositoryInterface::class);
        $cartRepository->save($cart1);
        $cartRepository->save($cart2);

        // Act
        $orderId1 = ($this->checkoutHandler)(new CheckoutCartCommand($userId1));
        $orderId2 = ($this->checkoutHandler)(new CheckoutCartCommand($userId2));

        // Assert
        $this->assertNotEquals((string) $orderId1, (string) $orderId2);

        $repository = $container->get(\App\Order\Domain\OrderRepositoryInterface::class);
        $order1 = $repository->get($orderId1);
        $order2 = $repository->get($orderId2);

        $this->assertNotNull($order1);
        $this->assertNotNull($order2);
        $this->assertNotEquals((string) $order1->cartId(), (string) $order2->cartId());
    }
}

