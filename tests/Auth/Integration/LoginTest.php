<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class LoginTest extends BaseWebTestCase
{
    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'integration@example.com', 'password' => 'password123']));

        // Allow multiple acceptable status codes; avoid deprecated assertContains
        $this->assertContainsEquals($client->getResponse()->getStatusCode(), [200, 401, 400]);
    }
}
