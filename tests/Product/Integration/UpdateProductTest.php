<?php

namespace App\Tests\Product\Integration;

use App\Tests\Factory\ProductFactory;
use App\Tests\TestCase\BaseWebTestCase;

final class UpdateProductTest extends BaseWebTestCase
{
    public function testUpdateProduct(): void
    {
        $client = $this->createAuthenticatedClient(true);

        // create product
        $productToEdit = ProductFactory::createOne([
            'sku' => 'sku-'.uniqid(),
            'name' => 'To Update',
            'price' => 3.50,
            'currency' => 'EUR',
            'stock' => 1,
        ]);

        // update
        $update = ['name' => 'Updated name', 'price' => 4.99, 'currency' => 'EUR'];
        $client->request('PUT', '/api/products/'.$productToEdit->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($update));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Updated name', $data['name']);
    }
}
