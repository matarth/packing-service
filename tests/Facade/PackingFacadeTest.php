<?php

declare(strict_types=1);

namespace App\Tests\Facade;

use App\DTO\PackingRequestDTO;
use App\Entity\Packaging;
use App\Facade\PackingFacade;
use App\Input\PackingInput;
use App\Input\ProductInput;
use App\Repository\PackagingRepository;
use App\Service\PackingService\PackingServiceInterface;
use App\ValueObject\PackingResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class PackingFacadeTest extends TestCase
{
    public function testBuildsServiceRequestWithAvailablePackagings(): void
    {
        $input = new PackingInput([
            new ProductInput(width: 1.0, height: 2.0, length: 3.0, weight: 4.0),
        ]);
        $packagings = [new Packaging(width: 2.5, height: 3.0, length: 1.0, maxWeight: 20.0)];
        $doctrineRepository = $this->createMock(EntityRepository::class);
        $doctrineRepository->expects(self::once())->method('findAll')->willReturn($packagings);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Packaging::class)
            ->willReturn($doctrineRepository);
        $service = $this->createMock(PackingServiceInterface::class);
        $service->expects(self::once())
            ->method('findSmallestBox')
            ->with(self::callback(static function (PackingRequestDTO $request) use ($input): bool {
                return $request->packingInput === $input
                    && count($request->packagings) === 1
                    && $request->packagings[0]->width === 2.5;
            }))
            ->willReturn(new PackingResult('small-box'));

        $facade = new PackingFacade($service, new PackagingRepository($entityManager));

        self::assertSame([
            'data' => ['box' => 'small-box'],
            'error' => null,
        ], $facade->findSmallestBox($input)->jsonSerialize());
    }
}
