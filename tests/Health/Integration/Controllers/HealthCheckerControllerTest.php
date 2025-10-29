<?php

namespace App\Tests\Health\Integration\Controllers;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthCheckerControllerTest extends WebTestCase
{
    public function test_health_returns_ok(): void
    {
        $client = static::createClient();
        $client->request('GET', 'api/health');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
