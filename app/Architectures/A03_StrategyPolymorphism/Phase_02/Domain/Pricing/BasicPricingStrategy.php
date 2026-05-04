<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Pricing;

class BasicPricingStrategy implements PricingStrategy
{
    public function calculateProduct(array $product, int $days): float
    {
        return $product['price_per_night'] * $days;
    }

    public function calculateExtra(array $extra, int $days, array $extraDates): float
    {
        if ($extra['type'] === 'per_night') {
            $daysApplied = empty($extraDates)
                ? $days
                : count($extraDates);

            return $extra['price'] * $daysApplied;
        }

        return $extra['price'];
    }
}
