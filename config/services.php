<?php

declare(strict_types=1);

use App\Facade\PackingFacade;
use App\Service\PackingService\LocalPackingService;
use App\Service\PackingService\PackingServiceInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(PackingServiceInterface::class, LocalPackingService::class);
    $services
        ->set(PackingFacade::class, PackingFacade::class)
        ->arg('$packingService', service(PackingServiceInterface::class));
};
