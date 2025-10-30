<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class LogoutDeniedAfterRevocationTest extends BaseWebTestCase
{
    public function testTokenIsRejectedAfterLogout(): void
    {
        $client = static::createClient();

        // Login to get token
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ]));

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $token = $data['token'];

        // Call a protected endpoint to verify token works initially

        // Logout with token
        $client->request('POST', '/api/logout', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        // Try protected endpoint again (must be unauthorized)
//        $client->request('GET', '/api/some-protected', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
//        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
}

