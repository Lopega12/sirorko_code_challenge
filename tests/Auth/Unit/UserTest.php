<?php

namespace App\Tests\Auth\Unit;

use App\Auth\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserIdentifierAndRoles(): void
    {
        $email = 'unit@example.com';
        $user = new User($email, 'dummyhash');

        $this->assertSame($email, $user->getUserIdentifier());
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertIsString($user->getId());
    }
}

