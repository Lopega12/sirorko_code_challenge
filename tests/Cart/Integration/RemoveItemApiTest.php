<?php

namespace App\Tests\Cart\Integration;

use App\Tests\Factory\UserFactory;
use App\Tests\TestCase\BaseWebTestCase;
use App\Tests\Factory\ProductFactory;
use App\Tests\Factory\CartFactory;
use function Zenstruck\Foundry\faker;

final class RemoveItemApiTest extends BaseWebTestCase
{
    public function test_remove_item_from_cart(): void
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

        CartFactory::createOneWithItems($itemsSpec, $user->getId(), self::$em);

        $randProductIndex = faker()->numberBetween(0, count($products) - 1);
        $prod = $products[$randProductIndex];

        // Borrar el item del carrito
        $client->request('DELETE', '/api/cart/items/' . $prod->getId());
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        // Obtener el carrito y verificar que el item ha sido eliminado
        $client->request('GET', '/api/cart/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data['items']);
        $this->assertCount(count($products)-1, $data['items']);
    }
}

