<?php

namespace App\Auth\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class LoginRateLimiter
{
    private RateLimiterFactory $factory;
    private string $prefix;

    public function __construct(RateLimiterFactory $factory, string $prefix = 'login_attempts')
    {
        $this->factory = $factory;
        $this->prefix = $prefix;
    }

    /**
     * Consumir tokens para una clave dada y retornar el resultado de RateLimit.
     * La clave debe ser única por cliente (ej: ip o email).
     */
    public function consumeByKey(string $key, int $tokens = 1): RateLimit
    {
        $limiter = $this->factory->create($this->prefix.':'.$key);

        return $limiter->consume($tokens);
    }

    /**
     * Método de conveniencia para consumir tokens usando la petición (por IP).
     */
    public function consumeRequest(Request $request, int $tokens = 1): RateLimit
    {
        $key = $request->getClientIp() ?? 'anonymous';

        return $this->consumeByKey($key, $tokens);
    }

    /**
     * Intentar resetear el estado del limitador para una clave si la implementación lo soporta.
     */
    public function resetKey(string $key): void
    {
        $limiter = $this->factory->create($this->prefix.':'.$key);
        if (method_exists($limiter, 'reset')) {
            $limiter->reset();
        }
    }
}
