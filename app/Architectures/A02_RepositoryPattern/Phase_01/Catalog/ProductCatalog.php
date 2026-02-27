<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_01\Catalog;

class ProductCatalog
{
    public static function products(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Habitación estándar - Hotel A',
                'price_per_night' => 100,
            ],
            2 => [
                'id' => 2,
                'name' => 'Habitaci�n premium - Hotel A',
                'price_per_night' => 180,
            ],
        ];
    }

    public static function extras(): array
    {
        return [
            10 => [
                'id' => 10,
                'name' => 'Desayuno',
                'price' => 20,
                'type'  => 'per_night',
            ],
            11 => [
                'id' => 11,
                'name' => 'Spa',
                'price' => 50,
                'type'  => 'per_stay',
            ],
        ];
    }

    public static function findProduct(int $id): ?array
    {
        return self::products()[$id] ?? null;
    }

    public static function findExtra(int $id): ?array
    {
        return self::extras()[$id] ?? null;
    }
}