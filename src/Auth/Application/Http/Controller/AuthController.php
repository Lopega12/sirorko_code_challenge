<?php

namespace App\Auth\Application\Http\Controller;

use App\Auth\Application\Http\DTO\LoginRequest;
use App\Auth\Application\Security\TokenGeneratorInterface;
use App\Auth\Repository\UserRepository;
use OpenApi\Attributes as OA;
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

    #[OA\Post(
        path: '/api/login',
        summary: 'Login de usuario',
        description: 'Autentica un usuario y devuelve un JWT token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGc...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Credenciales inválidas'),
            new OA\Response(response: 429, description: 'Demasiados intentos de login'),
        ]
    )]
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
