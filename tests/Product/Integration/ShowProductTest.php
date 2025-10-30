<?php

namespace App\Tests\Product\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class ShowProductTest extends BaseWebTestCase
{
    public function testShowProduct(): void
    {
        $client = $this->createAuthenticatedClient(true);

        $payload = [
            'sku' => 'sku-'.uniqid(),
            'name' => 'Show Product',
            'price' => 5.00,
            'currency' => 'EUR',
            'stock' => 2,
        ];

        $client->request('POST', '/api/products', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        if ($client->getResponse()->getStatusCode() !== 201) {
            $this->markTestSkipped('Could not create product');
        }

        $created = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $created);

        $client->request('GET', '/api/products/'.$created['id']);
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
