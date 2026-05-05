<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_04\Catalog;

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

    public static function taxRates(): array
    {
        return [
            'hotel' => 0.10,
            'event' => 0.05,
        ];
    }

    public static function commissionRates(): array
    {
        return [
            'event' => 0.03,
        ];
    }

    public static function typeRestrictions(): array
    {
        return [
            'event' => [
                'min_nights' => 3,
            ],
        ];
    }

    public static function earlyBookingDiscounts(): array
    {
        return [
            60 => 0.10,
            30 => 0.05,
        ];
    }

    public static function seasonalSurcharge(): array
    {
        return [
            'high_season_months' => [7, 8],
            'surcharge_rate' => 0.15,
        ];
    }

    public static function seasonalDiscountStacking(): bool
    {
        return false;
    }
}
