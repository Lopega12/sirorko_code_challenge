<?php

namespace App\Tests\Auth\Integration;

use App\Tests\Factory\UserFactory;
use App\Tests\TestCase\BaseWebTestCase;

final class LogoutRevocationTest extends BaseWebTestCase
{
    public function testLogoutPersistsRevokedJti(): void
    {
        $client = $this->createAuthenticatedClient(true);
        //obtain token from client
       $token =  $client->getServerParameter('HTTP_AUTHORIZATION', function ($header) use (&$token) {
            $token = str_replace('Bearer ', '', $header);
            return $token;
        });
        // Logout with token
        $client->request('POST', '/api/logout', [], []);
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

