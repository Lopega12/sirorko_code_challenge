<?php

namespace App\Tests\Factory;

use App\Auth\Domain\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use function Zenstruck\Foundry\faker;

final class UserFactory extends PersistentObjectFactory
{

    protected function defaults(): array|callable
    {
        return [
            'email' => faker()->unique()->safeEmail(),
            'passwordHash' => password_hash('password123', PASSWORD_BCRYPT),
            'roles' => ['ROLE_USER'],
        ];
    }

    public static function class(): string
    {
        return User::class;
    }
}

