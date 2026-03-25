<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Catalog;

class ProductCatalog
{
    public static function findProduct(int $id): ?array
    {
        return self::products()[$id] ?? null;
    }

    public static function findExtra(int $id): ?array
    {
        return self::extras()[$id] ?? null;
    }

    private static function products(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Habitación estándar - Hotel A',
                'price_per_night' => 100,
            ],
            2 => [
                'id' => 2,
                'name' => 'Habitacion premium - Hotel A',
                'price_per_night' => 180,
            ],
        ];
    }

    private static function extras(): array
    {
        return [
            10 => [
                'id' => 10,
                'name' => 'Desayuno',
                'price' => 20,
                'type' => 'per_night',
            ],
            11 => [
                'id' => 11,
                'name' => 'Spa',
                'price' => 50,
                'type' => 'per_stay',
            ],
        ];
    }
}
