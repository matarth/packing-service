<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a box available in the warehouse.
 *
 * Warehouse workers pack a set of products for a given order into one of these boxes.
 */
#[ORM\Entity]
class Packaging
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $width;

    #[ORM\Column(type: Types::FLOAT)]
    private float $height;

    #[ORM\Column(type: Types::FLOAT)]
    private float $length;

    #[ORM\Column(type: Types::FLOAT)]
    private float $smallEdge;

    #[ORM\Column(type: Types::FLOAT)]
    private float $middleEdge;

    #[ORM\Column(type: Types::FLOAT)]
    private float $longEdge;

    #[ORM\Column(type: Types::FLOAT)]
    private float $maxWeight;

    public function __construct(float $width, float $height, float $length, float $maxWeight)
    {
        $this->width = $width;
        $this->height = $height;
        $this->length = $length;
        $edges = [$width, $height, $length];
        sort($edges, SORT_NUMERIC);
        $this->smallEdge = $edges[0];
        $this->middleEdge = $edges[1];
        $this->longEdge = $edges[2];
        $this->maxWeight = $maxWeight;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function getSmallEdge(): float
    {
        return $this->smallEdge;
    }

    public function getMiddleEdge(): float
    {
        return $this->middleEdge;
    }

    public function getLongEdge(): float
    {
        return $this->longEdge;
    }

    public function getMaxWeight(): float
    {
        return $this->maxWeight;
    }
}
