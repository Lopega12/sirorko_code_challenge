<?php

namespace App\Tests\Auth\Unit;

use App\Auth\Application\Security\JwtTokenGenerator;
use App\Auth\Domain\User;
use PHPUnit\Framework\TestCase;

final class JwtTokenGeneratorTest extends TestCase
{
    public function testGenerateContainsJtiAndExpAndSub(): void
    {
        $secret = 'test_secret_123';
        $generator = new JwtTokenGenerator($secret);

        $user = new User('unit@example.com', password_hash('pwd', PASSWORD_BCRYPT));
        $ttl = 120;
        $token = $generator->generate($user, $ttl);

        $this->assertIsString($token);
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
        $payload = json_decode($payloadJson, true);

        $this->assertArrayHasKey('jti', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('sub', $payload);

        $this->assertSame($user->getId(), $payload['sub']);
        $this->assertEqualsWithDelta(time() + $ttl, $payload['exp'], 3);

        // jti should look like a UUID
        $this->assertMatchesRegularExpression('/^[0-9a-fA-F-]{36}$/', $payload['jti']);

        // verify signature correctness (recompute)
        $expectedSig = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true);
        $expectedB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');
        $this->assertSame($expectedB64, $sigB64);
    }
}

