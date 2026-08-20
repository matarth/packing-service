<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity]
#[ORM\Index(name: 'cached_packing_result_cache_key_idx', fields: ['cacheKey'])]
class CachedPackingResult
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    /** @phpstan-ignore property.unusedType */
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 80)]
    private string $cacheKey;

    #[ORM\ManyToOne(targetEntity: Packaging::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Packaging $packaging;

    public function __construct(string $cacheKey, Packaging $packaging)
    {
        $this->cacheKey = $cacheKey;
        $this->packaging = $packaging;
    }

    public function getId(): int
    {
        return $this->id
            ?? throw new LogicException(
                'Cached packing result has no ID. Persist and flush it before requesting its ID.',
            );
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function getPackaging(): Packaging
    {
        return $this->packaging;
    }
}
