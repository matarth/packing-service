<?php

declare(strict_types=1);

use App\Facade\PackingFacade;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Doctrine\ORM\EntityManagerInterface;

$container = new ContainerBuilder();
/** @var EntityManagerInterface $entityManager */
$entityManager = require __DIR__ . '/bootstrap.php';
$container->set(EntityManagerInterface::class, $entityManager);

$loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../config'));
$loader->load('services.php');

$container->getDefinition(PackingFacade::class)->setPublic(true);

$container->compile();

return $container;
