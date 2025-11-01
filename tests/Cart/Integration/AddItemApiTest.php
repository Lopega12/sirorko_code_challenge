<?php

namespace App\Tests\Cart\Integration;

use App\Tests\Factory\CartFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\TestCase\BaseWebTestCase;
use App\Tests\Factory\ProductFactory;
use function Zenstruck\Foundry\faker;

final class AddItemApiTest extends BaseWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

    }

    public function test_add_item_and_get_cart(): void
    {
        $client = $this->createAuthenticatedClient(false);

        // Create a product using the ProductFactory (Foundry)
        $product = ProductFactory::createOne([
            'sku' => 'sku-test-'.uniqid(),
            'name' => 'Integration Bottle',
            'price' => 9.99,
            'currency' => 'EUR',
            'stock' => 10,
        ]);

        // Add item via API
        $payload = [
            'product_id' => $product->getId(),
            'quantity' => 2,
        ];

        $client->request('POST', '/api/cart/items', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $this->assertSame(201, $client->getResponse()->getStatusCode(), 'Add item should return 201');

        // Get cart
        $client->request('GET', '/api/cart/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertCount(1, $data['items']);
        $this->assertEquals(2, $data['items'][0]['quantity']);
    }

//    public function test_checkout_creates_order(): void
//    {
//        $user = UserFactory::createOne()->object();
//        $client = static::createClient();
//        $client->loginUser($user);
//        // Create product and add to cart via factory
//        $products = ProductFactory::createMany(3,[
//            'stock' => faker()->numberBetween(1, 10),
//        ]);
//        CartFactory::createOne([
//            'userId' => $user->getId(),
//            'items' =>  array_map(fn($product) => [
//                'product_id' => $product->getId(),
//                'name' => $product->getName(),
//                'price' => $product->getPrice(),
//                'quantity' => faker()->numberBetween(1, 3),
//            ], $products)
//        ]);
//
//        // Checkout
//        $client->request('POST', '/api/cart/checkout');
//        $this->assertSame(200, $client->getResponse()->getStatusCode());
//        $data = json_decode($client->getResponse()->getContent(), true);
//        $this->assertArrayHasKey('order_id', $data);
//
//        // Verify orders table has row
//        $conn = self::$em->getConnection();
//        $count = (int) $conn->fetchOne('SELECT COUNT(*) FROM orders');
//        $this->assertGreaterThanOrEqual(1, $count);
//    }
}
