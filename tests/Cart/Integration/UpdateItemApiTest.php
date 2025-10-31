<?php

namespace App\Tests\Cart\Integration;

use App\Auth\Domain\User;

use App\Tests\Factory\UserFactory;
use App\Tests\TestCase\BaseWebTestCase;
use App\Tests\Factory\ProductFactory;
use App\Tests\Factory\CartFactory;
use function Zenstruck\Foundry\faker;

final class UpdateItemApiTest extends BaseWebTestCase
{

    public function test_update_item_quantity_in_cart(): void
    {
        $client = static::createClient();

        // create user via Foundry and obtain entity
        $userProxy = UserFactory::createOne(['roles' => ['ROLE_USER']]);
        $user = method_exists($userProxy, 'object') ? $userProxy->object() : $userProxy;
        $client->loginUser($user);

        // Persist cart for user with one item
        $products = ProductFactory::createMany(3,[
            'stock' => faker()->numberBetween(1, 10),
        ]);
        //Crear con la factory el carrito con varios items
        $itemsSpec = [];
        foreach ($products as $product) {
            $itemsSpec[] = [
                'product' => $product,
                'quantity' => faker()->numberBetween(1, 3),
            ];
        }

        // Use helper that creates the Cart via Foundry and persists items using the repository
        CartFactory::createOneWithItems($itemsSpec, $user->getId(), self::$em);

        $randProductIndex = faker()->numberBetween(0, count($products) - 1);
        $prod = $products[$randProductIndex];

        // Update quantity to 3
        $payload = ['quantity' => 3];
        $client->request('PUT', '/api/cart/items/'.$prod->getId(), [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload));
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Get cart and verify quantity updated
        $client->request('GET', '/api/cart/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(count($products), $data['items']);
        $this->assertEquals(3, $data['items'][$randProductIndex]['quantity']);
    }
}
