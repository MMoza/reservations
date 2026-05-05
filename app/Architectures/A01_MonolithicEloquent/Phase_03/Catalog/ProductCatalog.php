<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_03\Catalog;

class ProductCatalog
{
    public static function products(): array
    {
        return [
            1 => [
                'id' => 1,
                'product_type' => 'hotel',
                'name' => 'Habitación estándar - Hotel A',
                'price_per_night' => 100,
            ],
            2 => [
                'id' => 2,
                'product_type' => 'hotel',
                'name' => 'Habitación premium - Hotel A',
                'price_per_night' => 180,
            ],
            3 => [
                'id' => 3,
                'product_type' => 'event',
                'name' => 'Sala de conferencias - Centro de eventos',
                'price_per_night' => 300,
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
                'charge_type'  => 'per_night',
            ],
            11 => [
                'id' => 11,
                'name' => 'Spa',
                'price' => 50,
                'charge_type'  => 'per_stay',
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
