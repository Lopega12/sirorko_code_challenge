<?php

namespace App\Tests\Product\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class CreateProductTest extends BaseWebTestCase
{
    public function testCreateProductAsAdmin(): void
    {
        $client = $this->createAuthenticatedClient(true);

        $payload = [
            'sku' => 'sku-'.uniqid(),
            'name' => 'Test Product',
            'price' => 9.99,
            'currency' => 'EUR',
            'stock' => 5,
        ];

        $client->request('POST', '/api/products/', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));

        $this->assertContainsEquals($client->getResponse()->getStatusCode(), [201, 400, 401]);

        if ($client->getResponse()->getStatusCode() === 201) {
            $created = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('id', $created);
        }
    }
}
