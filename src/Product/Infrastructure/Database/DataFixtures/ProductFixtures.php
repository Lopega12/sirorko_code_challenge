<?php

namespace App\Product\Infrastructure\Database\DataFixtures;

use App\Auth\Domain\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ProductFixtures extends Fixture
{
    public function __construct()
    {
    }

    public function load(ObjectManager $manager): void
    {
        /*
         *Generar productos de prueba, priniciplamente ropa deportiva
         */

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
