<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Catalog;

class ProductCollection
{
    public function __construct(private array $products, private array $extras){}

    public function getProduct(int $id): ?array
    {
        return $this->products[$id] ?? null;
    }

    public function getExtra(int $id): ?array
    {
        return $this->extras[$id] ?? null;
    }
}
