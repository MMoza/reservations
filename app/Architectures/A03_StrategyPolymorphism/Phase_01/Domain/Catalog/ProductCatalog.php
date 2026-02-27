<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Catalog;

class ProductCatalog
{
    private static array $products = [
        1 => [
            'id' => 1,
            'name' => 'Standard Room',
            'price_per_night' => 100,
        ],
        2 => [
            'id' => 2,
            'name' => 'Deluxe Room',
            'price_per_night' => 180,
        ],
    ];

    private static array $extras = [
        10 => [
            'id' => 10,
            'name' => 'Breakfast',
            'price' => 15,
            'type' => 'per_night',
        ],
        11 => [
            'id' => 11,
            'name' => 'Parking',
            'price' => 20,
            'type' => 'fixed',
        ],
    ];

    public static function findProductsByIds(array $ids): array
    {
        return collect($ids)
            ->unique()
            ->filter(fn ($id) => isset(self::$products[$id]))
            ->mapWithKeys(fn ($id) => [$id => self::$products[$id]])
            ->toArray();
    }

    public static function findExtrasByIds(array $ids): array
    {
        return collect($ids)
            ->unique()
            ->filter(fn ($id) => isset(self::$extras[$id]))
            ->mapWithKeys(fn ($id) => [$id => self::$extras[$id]])
            ->toArray();
    }
}