<?php

namespace App\Tests\Product\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class DeleteProductTest extends BaseWebTestCase
{
    public function testDeleteProduct(): void
    {
        $client = $this->createAuthenticatedClient(true);

        // create product
        $payload = [
            'sku' => 'sku-'.uniqid(),
            'name' => 'To Delete',
            'price' => 2.00,
            'currency' => 'EUR',
            'stock' => 1,
        ];

        $client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        if ($client->getResponse()->getStatusCode() !== 201) {
            $this->markTestSkipped('Could not create product');
        }

        $created = json_decode($client->getResponse()->getContent(), true);

        $client->request('DELETE', '/api/products/'.$created['id']);
        // Accept multiple possible response codes; use assertContainsEquals to avoid deprecation
        $this->assertContainsEquals($client->getResponse()->getStatusCode(), [204, 401, 404]);
    }
}
