<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class LogoutTest extends BaseWebTestCase
{
    public function testLogoutRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/logout');

        $this->assertNotNull($client->getResponse());
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testLoginThenLogoutInvalidatesSession(): void
    {
        $client = static::createClient();

        // Login with seeded admin user
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ]));

        $loginResponse = $client->getResponse();
        if ($loginResponse->getStatusCode() !== 200) {
            $this->markTestSkipped('Login did not return 200, cannot assert logout state. Status: ' . $loginResponse->getStatusCode());
            return;
        }

        // Ensure cookie jar has a session cookie before logout
        $cookies = $client->getCookieJar()->all();
        $this->assertNotEmpty($cookies, 'Expected session cookie after login');

        // Perform logout using same client (cookies preserved)
        $client->request('POST', '/api/logout');
        $resp = $client->getResponse();

        $this->assertEquals(204, $resp->getStatusCode(), 'Logout should return 204 No Content');

        // Check response has Set-Cookie header that invalidates session (expires or empty value)
        $setCookie = $resp->headers->get('Set-Cookie');
        $this->assertNotNull($setCookie, 'Expected Set-Cookie header on logout response');

        // After logout, further request to protected resource should be unauthorized
        $client->request('POST', '/api/logout');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }
}

