<?php

namespace App\Auth\Application\Security;

use App\Auth\Domain\User;

interface TokenGeneratorInterface
{
    /**
     * Genera un token para el usuario dado, con TTL en segundos.
     * Retorna el token firmado como string.
     */
    public function generate(User $user, int $ttl = 3600): string;
}
