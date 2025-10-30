<?php

namespace App\Tests\Product\Integration;

use App\Tests\Factory\ProductFactory;
use App\Tests\TestCase\BaseWebTestCase;

final class DeleteProductTest extends BaseWebTestCase
{
    public function testDeleteProduct(): void
    {
        $client = $this->createAuthenticatedClient(true);
        $product = ProductFactory::createOne([
            'sku' => 'list-find-'.uniqid(),
            'currency' => 'EUR',
        ]);

        $client->request('DELETE', '/api/products/'.$product->getId());
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
