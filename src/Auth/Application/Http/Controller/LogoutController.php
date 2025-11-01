<?php

namespace App\Auth\Application\Http\Controller;

use App\Auth\Application\Security\TokenRevokerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
final class LogoutController
{
    public function __construct(private TokenRevokerInterface $revoker, private TokenStorageInterface $tokenStorage)
    {
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout de usuario',
        description: 'Cierra la sesión del usuario y revoca el token JWT',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Logout exitoso',
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $session = $request->getSession();

        // Clear security token to ensure the user is no longer authenticated
        if ($this->tokenStorage) {
            $this->tokenStorage->setToken(null);
        }

        // Remove firewall-specific security key from session if present
        if ($session && $session->isStarted()) {
            // common key format: _security_<firewallName> (default 'main')
            if ($session->has('_security_main')) {
                $session->remove('_security_main');
            }
            $session->invalidate();
        }

        $auth = $request->headers->get('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            $parts = explode('.', $token);
            if (3 === count($parts)) {
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                $jti = $payload['jti'] ?? null;
                $exp = isset($payload['exp']) ? (int) $payload['exp'] : null;
                if ($jti && $exp) {
                    $this->revoker->revokeByJti($jti, $exp);
                }
            }
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
