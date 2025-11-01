<?php

namespace App\Tests\TestCase;

use App\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Zenstruck\Foundry\Test\Factories;

abstract class BaseWebTestCase extends WebTestCase
{
    use Factories;

    protected static ?\Doctrine\ORM\EntityManagerInterface $em = null;
    /**
     * Cache simple de tokens por user id para evitar regenerarlos en cada test
     * @var array<string,string>
     */
    protected static array $tokenCache = [];

    public static function setUpBeforeClass(): void
    {
        // Iniciar el kernel para obtener el contenedor sin usar createClient() (evita conflictos con Foundry)
        static::bootKernel();

        // Get container and doctrine
        $container = static::getContainer();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $doctrine->getManager();
        self::$em = $em;

        $metadata = self::$em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new SchemaTool(self::$em);
            try {
                $tool->dropSchema($metadata);
            } catch (\Throwable $e) {
                // ignore
            }
            $tool->createSchema($metadata);
        }
        // Apagar el kernel para que createClient() pueda ser llamado de forma segura en cada test
        static::ensureKernelShutdown();
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$em) {
            $metadata = self::$em->getMetadataFactory()->getAllMetadata();
            if (!empty($metadata)) {
                $tool = new SchemaTool(self::$em);
                try {
                    $tool->dropSchema($metadata);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            self::$em->close();
            self::$em = null;
        }

        parent::tearDownAfterClass();
    }

    /**
     * Create a client and authenticate it with an admin or normal user.
     *
     * @param bool $isAdmin true => use an admin user, false => use a normal user
     */
    protected function createAuthenticatedClient(bool $isAdmin = true): KernelBrowser
    {
        // Asegurar que el kernel está apagado antes de crear el cliente — Foundry puede haberlo iniciado en un hook
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $doctrine = $container->get('doctrine');

        $role = $isAdmin ? 'ROLE_ADMIN' : 'ROLE_USER';
        $user = UserFactory::createOne([
            'roles' => [$role],
        ]);

        // Si existe el servicio JwtTokenGenerator, usarlo para crear un token y establecer el header Authorization
        $jwtServiceId = \App\Auth\Application\Security\JwtTokenGenerator::class;
        $userId = $user->getId();
        if ($container->has($jwtServiceId)) {
            if (!isset(self::$tokenCache[$userId])) {
                $generator = $container->get($jwtServiceId);
                $token = $generator->generate($user, 3600);
                self::$tokenCache[$userId] = $token;
            } else {
                $token = self::$tokenCache[$userId];
            }

            $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
            return $client;
        }

        // Fallback final a login por sesión (útil si los tests se ejecutan en modo stateful)
        $client->loginUser($user);
        return $client;
    }
}
