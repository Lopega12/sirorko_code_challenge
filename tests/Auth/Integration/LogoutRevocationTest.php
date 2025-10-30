<?php

namespace App\Tests\Auth\Integration;

use App\Tests\TestCase\BaseWebTestCase;

final class LogoutRevocationTest extends BaseWebTestCase
{
    public function testLogoutPersistsRevokedJti(): void
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

        // Logout with token
        $client->request('POST', '/api/logout', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $this->assertSame(204, $client->getResponse()->getStatusCode());

        // Extract jti from token and check DB
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertArrayHasKey('jti', $payload);
        $jti = $payload['jti'];

        $em = self::$em;
        $row = $em->getConnection()->fetchOne('SELECT jti FROM revoked_tokens WHERE jti = ?', [$jti]);
        $this->assertNotFalse($row, 'Expected revoked jti in database');
    }
}

