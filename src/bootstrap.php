<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

require __DIR__ . '/../vendor/autoload.php';

$config = new Configuration();
$config->setMetadataDriverImpl(new AttributeDriver([__DIR__]));
$config->setNamingStrategy(new UnderscoreNamingStrategy());
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('DoctrineProxies');
$config->setAutoGenerateProxyClasses(true);
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
