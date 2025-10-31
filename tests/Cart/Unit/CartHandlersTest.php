<?php

namespace App\Tests\Cart\Unit;

use PHPUnit\Framework\TestCase;
use App\Cart\Domain\ProductId;
use App\Cart\Domain\Money;
use App\Cart\Application\Command\AddItemToCartCommand;
use App\Cart\Application\Handler\AddItemToCartHandler;
use App\Cart\Infrastructure\InMemoryCartRepository;
use App\Cart\Application\Command\UpdateItemQuantityCommand;
use App\Cart\Application\Handler\UpdateItemQuantityHandler;
use App\Cart\Application\Command\RemoveItemFromCartCommand;
use App\Cart\Application\Handler\RemoveItemFromCartHandler;
use App\Cart\Application\Command\CheckoutCartCommand;
use App\Cart\Application\Handler\CheckoutCartHandler;
use App\Order\Infrastructure\InMemoryOrderRepository;
use App\Product\Infrastructure\Repository\InMemoryProductRepository;
use App\Product\Domain\Entity\Product;

final class CartHandlersTest extends TestCase
{
    public function test_add_update_remove_and_checkout_flow(): void
    {
        $cartRepo = new InMemoryCartRepository();
        $orderRepo = new InMemoryOrderRepository();
        $productRepo = new InMemoryProductRepository();

        $addHandler = new AddItemToCartHandler($cartRepo, $productRepo);
        $updateHandler = new UpdateItemQuantityHandler($cartRepo);
        $removeHandler = new RemoveItemFromCartHandler($cartRepo);
        $checkoutHandler = new CheckoutCartHandler($cartRepo, $orderRepo);

        $userId = 'user-1';
        $productId1 = ProductId::fromString('prod-1');
        $productId2 = ProductId::fromString('prod-2');

        // create products in product repo
        $productRepo->save(new Product('sku-1', 'Siroko Bottle', 9.99, 'EUR', 10, null, (string) $productId1));
        $productRepo->save(new Product('sku-2', 'Siroko Cap', 4.5, 'EUR', 5, null, (string) $productId2));

        // Add two items
        $addHandler(new AddItemToCartCommand($userId, $productId1, 2));
        $addHandler(new AddItemToCartCommand($userId, $productId2, 1));

        $cart = $cartRepo->findByUserId($userId);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart->items());
        $this->assertEquals(2, $cart->items()[0]->quantity());

        // Update quantity
        $updateHandler(new UpdateItemQuantityCommand($userId, $productId1, 3));
        $cart = $cartRepo->findByUserId($userId);
        $this->assertEquals(3, $cart->items()[0]->quantity());

        // Remove item
        $removeHandler(new RemoveItemFromCartCommand($userId, $productId2));
        $cart = $cartRepo->findByUserId($userId);
        $this->assertCount(1, $cart->items());

        // Checkout
        $orderId = $checkoutHandler(new CheckoutCartCommand($userId));
        $this->assertNotNull($orderRepo->get($orderId));

        $order = $orderRepo->get($orderId);
        $this->assertEquals($cart->total()->toCents(), $order->total()->toCents());
    }
}
