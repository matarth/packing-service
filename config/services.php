<?php

declare(strict_types=1);

use App\Facade\PackingFacade;
use App\Repository\PackagingRepository;
use App\Service\PackingService\CachedPackingServiceDecorator;
use App\Service\PackingService\FailoverPackingService;
use App\Service\PackingService\LocalPackingService;
use App\Service\PackingService\PackingServiceInterface;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingApiClient;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set(ClientInterface::class, Client::class)
        ->args([[
            'http_errors' => false,
            'timeout' => 10.0,
        ]]);
    $services
        ->set(ShipmonkSamplePackingApiClient::class, ShipmonkSamplePackingApiClient::class)
        ->arg('$httpClient', service(ClientInterface::class));


    $services
        ->set(LocalPackingService::class, LocalPackingService::class)
        ->tag('packing.service', ['priority' => 100]);

    $services
        ->set(ShipmonkSamplePackingService::class, ShipmonkSamplePackingService::class)
        ->arg('$client', service(ShipmonkSamplePackingApiClient::class))
        ->tag('packing.service');
    $services
        ->set(FailoverPackingService::class, FailoverPackingService::class)
        ->arg('$packingServices', tagged_iterator('packing.service'));
    $services->set(CacheInterface::class, ArrayAdapter::class);
    $services
        ->set(CachedPackingServiceDecorator::class, CachedPackingServiceDecorator::class)
        ->arg('$packingService', service(FailoverPackingService::class))
        ->arg('$cache', service(CacheInterface::class));
    $services->alias(PackingServiceInterface::class, CachedPackingServiceDecorator::class);
    $services
        ->set(PackagingRepository::class, PackagingRepository::class)
        ->arg('$entityManager', service(EntityManagerInterface::class))
        ->tag('packing.repository');
    $services
        ->set(PackingFacade::class, PackingFacade::class)
        ->arg('$packingService', service(PackingServiceInterface::class))
        ->arg('$packagingRepository', service(PackagingRepository::class));
};
