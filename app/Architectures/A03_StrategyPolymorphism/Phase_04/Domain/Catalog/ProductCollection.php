<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Catalog;

class ProductCollection
{
    private array $products;
    private array $extras;

    public function __construct(array $products, array $extras)
    {
        $this->products = $products;
        $this->extras = $extras;
    }

    public function getProduct(int $id): ?array
    {
        return $this->products[$id] ?? null;
    }

    public function getExtra(int $id): ?array
    {
        return $this->extras[$id] ?? null;
    }

    public function allProducts(): array
    {
        return $this->products;
    }

    public function allExtras(): array
    {
        return $this->extras;
    }
}
