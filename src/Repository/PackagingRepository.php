<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PackagingRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /** @return list<Packaging> */
    public function findAll(): array
    {
        /** @var list<Packaging> $packagings */
        $packagings = $this->entityManager->getRepository(Packaging::class)->findAll();

        return $packagings;
    }
}
