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
        // Boot the kernel to get the container without using createClient() (avoids conflicts with Foundry)
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
        // shutdown kernel so createClient() can be called safely in each test
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
        // Ensure kernel is shutdown before creating the client — Foundry may have booted it in a before-hook
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();

        $doctrine = $container->get('doctrine');

        $role = $isAdmin ? 'ROLE_ADMIN' : 'ROLE_USER';
        $user = UserFactory::createOne([
            'roles' => [$role],
        ]);

        // If a JwtTokenGenerator service exists, use it to create a token and set Authorization header
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

        // Final fallback to session login (useful if tests run in stateful mode)
        $client->loginUser($user);
        return $client;
    }
}
