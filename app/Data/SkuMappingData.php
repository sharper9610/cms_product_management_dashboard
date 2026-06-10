<?php
// app/Data/SkuMappingData.php

namespace App\Data;

class SkuMappingData
{
    public function __construct(
        public readonly int    $parentSku,
        public readonly array  $childSkus,
        public readonly ?string $mappedAt,
    ) {}

    public static function fromArray(array $mapping): self
    {
        return new self(
            parentSku: (int) $mapping['parent_sku'],
            childSkus: isset($mapping['child_skus'])
                ? array_map('intval', $mapping['child_skus'])
                : null,
            mappedAt: $mapping['mapped_at'] ?? null,
        );
    }
}
