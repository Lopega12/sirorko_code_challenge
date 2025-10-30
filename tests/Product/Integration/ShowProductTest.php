<?php

namespace App\Tests\Product\Integration;

use App\Tests\Factory\ProductFactory;
use App\Tests\TestCase\BaseWebTestCase;

final class ShowProductTest extends BaseWebTestCase
{
    public function testShowProduct(): void
    {
        $client = $this->createAuthenticatedClient(true);
        $product = ProductFactory::createOne([
            'sku' => 'list-find-'.uniqid(),
            'name' => 'List Find Product',
            'price' => 9.99,
            'currency' => 'EUR',
            'stock' => 3,
        ]);
        $client->request('GET', '/api/products/'.$product->getId());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
