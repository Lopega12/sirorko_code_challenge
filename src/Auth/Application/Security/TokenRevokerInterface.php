<?php

namespace App\Auth\Application\Security;

interface TokenRevokerInterface
{
    /**
     * Revoca un token identificándolo por su jti y estableciendo su expiración.
     *
     * @param int $exp Timestamp UNIX
     */
    public function revokeByJti(string $jti, int $exp): void;
}
