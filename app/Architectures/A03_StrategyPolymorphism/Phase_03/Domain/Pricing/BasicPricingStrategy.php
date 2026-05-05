<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_03\Domain\Pricing;

use App\Architectures\A03_StrategyPolymorphism\Phase_03\Catalog\PricingRules;

class BasicPricingStrategy implements PricingStrategy
{
    public function calculateProduct(array $product, int $days): float
    {
        return $product['price_per_night'] * $days;
    }

    public function calculateExtra(array $extra, int $days, array $extraDates): float
    {
        if ($extra['charge_type'] === 'per_night') {
            $daysApplied = empty($extraDates) ? $days : count($extraDates);
            return $extra['price'] * $daysApplied;
        }

        return $extra['price'];
    }

    public function calculateTax(string $productType, float $basePrice): float
    {
        $rate = $this->getTaxRate($productType);
        return $basePrice * $rate;
    }

    public function calculateCommission(string $productType, float $basePrice): float
    {
        $rate = $this->getCommissionRate($productType);
        return $basePrice * $rate;
    }

    public function getTaxRate(string $productType): float
    {
        return PricingRules::taxRates()[$productType] ?? 0;
    }

    public function getCommissionRate(string $productType): float
    {
        return PricingRules::commissionRates()[$productType] ?? 0;
    }
}
