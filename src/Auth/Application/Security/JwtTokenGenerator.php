<?php

namespace App\Auth\Application\Security;

use App\Auth\Domain\User;
use Ramsey\Uuid\Uuid;

final class JwtTokenGenerator implements TokenGeneratorInterface
{
    public function __construct(private string $secret)
    {
    }

    public function generate(User $user, int $ttl = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => Uuid::uuid4()->toString(),
        ];

        $base64UrlEncode = function (string $data): string {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $headerB64 = $base64UrlEncode(json_encode($header));
        $payloadB64 = $base64UrlEncode(json_encode($payload));
        $sig = hash_hmac('sha256', $headerB64.'.'.$payloadB64, $this->secret, true);
        $sigB64 = $base64UrlEncode($sig);

        return $headerB64.'.'.$payloadB64.'.'.$sigB64;
    }
}
