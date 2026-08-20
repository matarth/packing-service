<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;

require __DIR__ . '/../vendor/autoload.php';

$config = ORMSetup::createAttributeMetadataConfiguration([__DIR__], true);
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$configuredDatabaseHost = getenv('PACKING_DATABASE_HOST') ?: ($_SERVER['PACKING_DATABASE_HOST'] ?? false);
$databaseHost = is_string($configuredDatabaseHost) ? $configuredDatabaseHost : 'shipmonk-packing-mysql';
$configuredDatabaseName = getenv('PACKING_DATABASE_NAME') ?: ($_SERVER['PACKING_DATABASE_NAME'] ?? false);
$databaseName = is_string($configuredDatabaseName) ? $configuredDatabaseName : 'packing';

return new EntityManager(DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => $databaseHost,
    'user' => 'root',
    'password' => 'secret',
    'dbname' => $databaseName,
]), $config);
