<?php

namespace App\Auth\Infrastructure\Database\DataFixtures;

use App\Auth\Domain\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = FakerFactory::create();

        // Admin fijo
        $adminEmail = 'admin@example.com';
        $adminPassword = 'admin123';

        $admin = new User($adminEmail, '');
        $adminHash = $this->passwordHasher->hashPassword($admin, $adminPassword);
        $admin->setPassword($adminHash);
        // asignar rol admin
        // el constructor añade ROLE_USER por defecto, así que lo dejamos
        $manager->persist($admin);

        // Usuarios de prueba
        for ($i = 0; $i < 5; ++$i) {
            $email = $faker->unique()->safeEmail();
            $plain = 'password';
            $user = new User($email, '');
            $hash = $this->passwordHasher->hashPassword($user, $plain);
            $user->setPassword($hash);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
