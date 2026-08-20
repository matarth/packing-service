<?php

declare(strict_types=1);

namespace App\Tests;

use App\Tests\Builder\PackagingBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use LogicException;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    private const TEST_DATABASE_HOST = 'shipmonk-packing-test-mysql';
    private const TEST_DATABASE_NAME = 'packing_test';

    protected EntityManagerInterface $entityManager;

    protected PackagingBuilder $packagingBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = require __DIR__ . '/../src/bootstrap.php';
        $this->entityManager = $entityManager;

        $connection = $this->entityManager->getConnection();
        $connectionParameters = $connection->getParams();
        if (
            ($connectionParameters['host'] ?? null) !== self::TEST_DATABASE_HOST
            || $connection->getDatabase() !== self::TEST_DATABASE_NAME
        ) {
            throw new LogicException('Refusing to reset a database that is not the dedicated test database.');
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->packagingBuilder = new PackagingBuilder($this->entityManager);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();
            $this->entityManager->clear();
            $this->entityManager->close();

            if ($connection->isConnected()) {
                $connection->close();
            }
        }

        parent::tearDown();
    }
}
