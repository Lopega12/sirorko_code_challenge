<?php

namespace App\Auth\Application\Security;

use App\Auth\Domain\RevokedToken;
use Doctrine\Persistence\ManagerRegistry;

final class DbTokenRevoker implements TokenRevokerInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function revokeByJti(string $jti, int $exp): void
    {
        $expiresAt = (new \DateTimeImmutable())->setTimestamp($exp);
        $revoked = new RevokedToken($jti, $expiresAt);
        $em = $this->doctrine->getManager();
        $em->persist($revoked);
        $em->flush();
    }
}
