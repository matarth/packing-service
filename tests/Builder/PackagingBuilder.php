<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Packaging;
use Doctrine\ORM\EntityManagerInterface;

final class PackagingBuilder
{
    private float $width = 1.0;

    private float $height = 1.0;

    private float $length = 1.0;

    private float $maxWeight = 1.0;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function withWidth(float $width): self
    {
        $builder = clone $this;
        $builder->width = $width;

        return $builder;
    }

    public function withHeight(float $height): self
    {
        $builder = clone $this;
        $builder->height = $height;

        return $builder;
    }

    public function withLength(float $length): self
    {
        $builder = clone $this;
        $builder->length = $length;

        return $builder;
    }

    public function withMaxWeight(float $maxWeight): self
    {
        $builder = clone $this;
        $builder->maxWeight = $maxWeight;

        return $builder;
    }

    public function build(): Packaging
    {
        $packaging = new Packaging(
            width: $this->width,
            height: $this->height,
            length: $this->length,
            maxWeight: $this->maxWeight,
        );
        $this->entityManager->persist($packaging);
        $this->entityManager->flush();

        return $packaging;
    }
}
