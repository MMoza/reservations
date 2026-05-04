<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_02\Catalog;

class PricingRules
{
    public static function volumeDiscounts(): array
    {
        return [
            14 => 0.20,
            7  => 0.10,
        ];
    }

    public static function combinedPromotions(): array
    {
        return [
            [
                'products' => [1, 2],
                'discount' => 0.05,
            ],
        ];
    }

    public static function minimumPrice(): float
    {
        return 500.00;
    }

    public static function extraRequirements(): array
    {
        return [
            11 => [
                'min_nights' => 3,
            ],
        ];
    }
}
