<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class AuthTest extends BaseWebTestCase
{
    public function testLoginEndpointExists(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'noone@example.com', 'password' => 'x']));
        $this->assertNotNull($client->getResponse());
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [200, 400, 401], true),
            'Expected response status 200/400/401, got '. $client->getResponse()->getStatusCode()
        );
    }
}
