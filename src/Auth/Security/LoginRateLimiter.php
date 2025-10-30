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
     * Consume tokens for a given key and return the RateLimit result.
     * Key should be unique per client (e.g. ip or email).
     */
    public function consumeByKey(string $key, int $tokens = 1): RateLimit
    {
        $limiter = $this->factory->create($this->prefix.':'.$key);

        return $limiter->consume($tokens);
    }

    /**
     * Convenience method to consume tokens using the request (by IP).
     */
    public function consumeRequest(Request $request, int $tokens = 1): RateLimit
    {
        $key = $request->getClientIp() ?? 'anonymous';

        return $this->consumeByKey($key, $tokens);
    }

    /**
     * Try to reset limiter state for a key if the limiter implementation supports it.
     */
    public function resetKey(string $key): void
    {
        $limiter = $this->factory->create($this->prefix.':'.$key);
        if (method_exists($limiter, 'reset')) {
            $limiter->reset();
        }
    }
}
