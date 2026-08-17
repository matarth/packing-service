<?php

declare(strict_types=1);

use App\Facade\PackingFacade;
use App\Repository\PackagingRepository;
use App\Service\PackingService\LocalPackingService;
use App\Service\PackingService\PackingServiceInterface;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingApiClient;
use App\Service\PackingService\ShipmonkSamplePackingService\ShipmonkSamplePackingService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
        ->set(PackingServiceInterface::class, LocalPackingService::class)
        ->tag('packing.service');

    $services
        ->set(ShipmonkSamplePackingService::class, ShipmonkSamplePackingService::class)
        ->tag('packing.service');
    $services
        ->set(PackagingRepository::class, PackagingRepository::class)
        ->arg('$entityManager', service(EntityManagerInterface::class))
        ->tag('packing.repository');
    $services
        ->set(PackingFacade::class, PackingFacade::class)
        ->arg('$packingService', service(PackingServiceInterface::class))
        ->arg('$packagingRepository', service(PackagingRepository::class));
};
