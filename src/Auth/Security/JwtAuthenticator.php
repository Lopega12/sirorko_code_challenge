<?php

namespace App\Auth\Security;

use App\Auth\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator implements AuthenticatorInterface
{
    public function __construct(private ManagerRegistry $doctrine, private UserRepository $users, private string $secret)
    {
    }

    public function supports(Request $request): ?bool
    {
        $auth = $request->headers->get('Authorization');

        return $auth && str_starts_with($auth, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $auth = $request->headers->get('Authorization');
        $token = substr($auth, 7);

        $parts = explode('.', $token);
        if (3 !== count($parts)) {
            throw new \RuntimeException('Invalid token format');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $data = $headerB64.'.'.$payloadB64;
        $sig = base64_decode(strtr($sigB64, '-_', '+/'));
        $expected = hash_hmac('sha256', $data, $this->secret, true);
        if (!hash_equals($expected, $sig)) {
            throw new \RuntimeException('Invalid token signature');
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
        if (!$payload || empty($payload['sub'])) {
            throw new \RuntimeException('Invalid token payload');
        }

        // check expiration
        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            throw new \RuntimeException('Token expired');
        }

        // check revoked jti
        $jti = $payload['jti'] ?? null;
        if ($jti) {
            $conn = $this->doctrine->getConnection();
            $row = $conn->fetchOne('SELECT jti FROM revoked_tokens WHERE jti = ?', [$jti]);
            if ($row) {
                throw new \RuntimeException('Token revoked');
            }
        }

        $userId = $payload['sub'];
        $user = $this->users->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function ($userIdentifier) use ($user) {
            return $user;
        }));
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        // Build an authenticated token from the Passport's user
        $user = $passport->getUser();
        if (!$user instanceof UserInterface) {
            throw new \RuntimeException('Passport does not contain a User');
        }

        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];

        return new UsernamePasswordToken($user, $firewallName, $roles);
    }

    public function createAuthenticatedToken(Passport $passport, string $firewallName): TokenInterface
    {
        // kept for compatibility, delegate to createToken
        return $this->createToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }
}
