<?php

namespace App\Tests\Cart\Integration;

use App\Tests\TestCase\BaseWebTestCase;
use App\Product\Domain\Entity\Product;

final class GetCartApiTest extends BaseWebTestCase
{
    public function test_get_empty_cart_returns_empty_structure(): void
    {
        $client = $this->createAuthenticatedClient(false);

        $client->request('GET', '/api/cart/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertIsArray($data['items']);
        $this->assertEquals(0, $data['total']);
    }
}

