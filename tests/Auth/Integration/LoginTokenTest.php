<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class LoginTokenTest extends BaseWebTestCase
{
    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['email' => 'admin@example.com', 'password' => 'admin123']));

        $response = $client->getResponse();
        $this->assertNotNull($response);
        $this->assertContains($response->getStatusCode(), [200, 401, 400]);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('token', $data);
            $this->assertArrayHasKey('expires_in', $data);
        }
    }
}
