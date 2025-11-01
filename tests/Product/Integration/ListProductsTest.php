<?php

namespace App\Tests\Product\Integration;

use App\Tests\TestCase\BaseWebTestCase;
use App\Tests\Factory\ProductFactory;

final class ListProductsTest extends BaseWebTestCase
{
    public function testReturns200AndJson(): void
    {
        $client = $this->createAuthenticatedClient(false);
       $client->request('GET', '/api/products/');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertJson((string) $client->getResponse()->getContent());

        $list = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($list);
    }

    public function testCreateProductAndFindItInList(): void
    {
        // Crear un producto directamente usando la factory (rápido)
        $product = ProductFactory::createOne([
            'sku' => 'list-find-'.uniqid(),
            'name' => 'List Find Product',
            'price' => 9.99,
            'currency' => 'EUR',
            'stock' => 3,
        ]);

        $createdId = $product->getId();

        $public = $this->createAuthenticatedClient(false);
        $public->request('GET', '/api/products/');
        $this->assertSame(200, $public->getResponse()->getStatusCode());

        $list = json_decode((string) $public->getResponse()->getContent(), true);
        $this->assertIsArray($list);

        $ids = array_column($list, 'id');
        // Usar assertContainsEquals para evitar deprecación de PHPUnit para pertenencia a array
        $this->assertContainsEquals($createdId, $ids, 'Created product should appear in list');
    }

    public function testIndexRespectsLimitParameter(): void
    {
        // create 3 products quickly
        ProductFactory::createMany(3);

        $public = $this->createAuthenticatedClient(false);
        $public->request('GET', '/api/products/?limit=2');

        $this->assertSame(200, $public->getResponse()->getStatusCode());
        $list = json_decode((string) $public->getResponse()->getContent(), true);
        $this->assertIsArray($list);
        $this->assertLessThanOrEqual(2, count($list));
    }

    public function testProductJsonShapeIncludesExpectedKeys(): void
    {
        $product = ProductFactory::createOne([
            'sku' => 'shape-'.uniqid(),
            'name' => 'Shape Product',
            'price' => 5.55,
            'currency' => 'EUR',
            'stock' => 2,
        ]);

        $createdId = $product->getId();

        $public = $this->createAuthenticatedClient(false);
        $public->request('GET', '/api/products/');
        $list = json_decode((string) $public->getResponse()->getContent(), true);

        // find created item
        $found = null;
        foreach ($list as $item) {
            if (isset($item['id']) && $item['id'] === $createdId) {
                $found = $item;
                break;
            }
        }

        $this->assertNotNull($found);
        $expectedKeys = ['id','sku','name','price','currency','stock'];
        foreach ($expectedKeys as $k) {
            $this->assertArrayHasKey($k, $found, sprintf('Product JSON must include key "%s"', $k));
        }
    }
}
