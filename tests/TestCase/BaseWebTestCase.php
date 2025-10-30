<?php

namespace App\Tests\TestCase;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;

abstract class BaseWebTestCase extends WebTestCase
{
    protected static ?\Doctrine\ORM\EntityManagerInterface $em = null;

    public static function setUpBeforeClass(): void
    {
        // Use createClient() to boot kernel in the expected way
        $client = static::createClient();

        // Get container and doctrine
        $container = static::getContainer();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');
        self::$em = $doctrine->getManager();

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

        // Insert test users if not present
        $conn = self::$em->getConnection();
        try {
            $existing = (int) $conn->fetchOne('SELECT COUNT(*) FROM users WHERE email = ?', ['admin@example.com']);
        } catch (\Throwable $e) {
            $existing = 0;
        }

        if ($existing === 0) {
            $id = (string) \Ramsey\Uuid\Uuid::uuid4();
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $roles = json_encode(['ROLE_ADMIN']);
            $conn->insert('users', ['id' => $id, 'email' => 'admin@example.com', 'password' => $hash, 'roles' => $roles]);
        }

        try {
            $existing2 = (int) $conn->fetchOne('SELECT COUNT(*) FROM users WHERE email = ?', ['integration@example.com']);
        } catch (\Throwable $e) {
            $existing2 = 0;
        }

        if ($existing2 === 0) {
            $id = (string) \Ramsey\Uuid\Uuid::uuid4();
            $hash = password_hash('password123', PASSWORD_BCRYPT);
            $roles = json_encode(['ROLE_USER']);
            $conn->insert('users', ['id' => $id, 'email' => 'integration@example.com', 'password' => $hash, 'roles' => $roles]);
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
}
