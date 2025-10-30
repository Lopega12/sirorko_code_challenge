<?php

namespace App\Auth\Application\Http\Controller;

use App\Auth\Application\Http\DTO\LoginRequest;
use App\Auth\Application\Security\TokenGeneratorInterface;
use App\Auth\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

#[AsController]
final class AuthController
{
    public function __construct(private UserRepository $userRepository, private UserPasswordHasherInterface $hasher, private RateLimiterFactoryInterface $loginAttemptsLimiter, private TokenGeneratorInterface $tokenGenerator)
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

        // generar token a través del servicio
        $tokenTtl = 3600; // 1 hora
        $token = $this->tokenGenerator->generate($user, $tokenTtl);

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
}
