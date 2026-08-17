<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Packaging;
use App\Input\PackingInput;
use Doctrine\ORM\EntityManagerInterface;

readonly class PackagingRepository
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

    /** @return list<Packaging> */
    public function findPotentiallyFitting(PackingInput $input): array
    {
        $requirements = $this->createRequirements($input);
        $query = $this->entityManager->createQueryBuilder()
            ->select('packaging')
            ->from(Packaging::class, 'packaging')
            ->where('packaging.smallEdge >= :smallEdge')
            ->andWhere('packaging.middleEdge >= :middleEdge')
            ->andWhere('packaging.longEdge >= :longEdge')
            ->andWhere('packaging.width * packaging.height * packaging.length >= :totalVolume')
            ->orderBy('packaging.id', 'ASC')
            ->setParameter('smallEdge', $requirements['smallEdge'])
            ->setParameter('middleEdge', $requirements['middleEdge'])
            ->setParameter('longEdge', $requirements['longEdge'])
            ->setParameter('totalVolume', $requirements['totalVolume'])
            ->getQuery();

        /** @var list<Packaging> $packagings */
        $packagings = $query->getResult();

        return $packagings;
    }

    /** @return array{smallEdge: float, middleEdge: float, longEdge: float, totalVolume: float} */
    private function createRequirements(PackingInput $input): array
    {
        $smallEdge = 0.0;
        $middleEdge = 0.0;
        $longEdge = 0.0;
        $totalVolume = 0.0;

        foreach ($input->products as $product) {
            $productSmallEdge = min($product->width, $product->height, $product->length);
            $productLongEdge = max($product->width, $product->height, $product->length);
            $productMiddleEdge = $product->width + $product->height + $product->length
                - $productSmallEdge
                - $productLongEdge;

            $smallEdge = max($smallEdge, $productSmallEdge);
            $middleEdge = max($middleEdge, $productMiddleEdge);
            $longEdge = max($longEdge, $productLongEdge);
            $totalVolume += $product->width * $product->height * $product->length;
        }

        return [
            'smallEdge' => $smallEdge,
            'middleEdge' => $middleEdge,
            'longEdge' => $longEdge,
            'totalVolume' => $totalVolume,
        ];
    }
}
