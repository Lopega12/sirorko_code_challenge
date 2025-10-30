<?php

namespace App\Auth\Application\Http\Controller;

use App\Auth\Application\Http\DTO\LoginRequest;
use App\Auth\Domain\User;
use App\Auth\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

#[AsController]
final class AuthController
{
    public function __construct(private UserRepository $userRepository, private UserPasswordHasherInterface $hasher, private RateLimiterFactoryInterface $loginAttemptsLimiter)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $dto = LoginRequest::fromRequest($request);
        if (empty($dto->email) || empty($dto->password)) {
            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $limiter = $this->loginAttemptsLimiter->create($dto->email);
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            return new JsonResponse(['error' => 'Too many attempts'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->userRepository->findOneByEmail($dto->email);
        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->hasher->isPasswordValid($user, $dto->password)) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // generar token
        $tokenTtl = 3600; // 1 hora
        $secret = $_ENV['APP_SECRET'] ?? ($_SERVER['APP_SECRET'] ?? null);
        if (!$secret) {
            // fallback temporal: no exponer secreto en producción
            $secret = 'dev_secret';
        }

        $token = $this->generateToken($user, $secret, $tokenTtl);

        return new JsonResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $tokenTtl,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }

    private function generateToken(User $user, string $secret, int $ttl = 3600): string
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
        $sig = hash_hmac('sha256', $headerB64.'.'.$payloadB64, $secret, true);
        $sigB64 = $base64UrlEncode($sig);

        return $headerB64.'.'.$payloadB64.'.'.$sigB64;
    }
}
