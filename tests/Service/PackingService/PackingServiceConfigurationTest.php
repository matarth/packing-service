<?php

declare(strict_types=1);

namespace App\Tests\Service\PackingService;

use App\Service\PackingService\FailoverPackingService;
use App\Service\PackingService\LocalPackingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class PackingServiceConfigurationTest extends TestCase
{
    public function testLocalPackingServiceIsTheLastFailoverProvider(): void
    {
        $container = new ContainerBuilder();
        $container->set(EntityManagerInterface::class, $this->createMock(EntityManagerInterface::class));

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../config'));
        $loader->load('services.php');
        $container->getDefinition(FailoverPackingService::class)->setPublic(true);
        $container->compile();

        /** @var FailoverPackingService $failoverService */
        $failoverService = $container->get(FailoverPackingService::class);
        $providers = $this->packingServices($failoverService);

        self::assertNotEmpty($providers);
        self::assertInstanceOf(LocalPackingService::class, array_pop($providers));
    }

    /** @return list<object> */
    private function packingServices(FailoverPackingService $failoverService): array
    {
        $property = new \ReflectionProperty($failoverService, 'packingServices');
        $property->setAccessible(true);

        /** @var iterable<object> $packingServices */
        $packingServices = $property->getValue($failoverService);

        return [...$packingServices];
    }
}
