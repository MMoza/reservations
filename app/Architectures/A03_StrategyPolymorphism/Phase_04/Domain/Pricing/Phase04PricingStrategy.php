<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Pricing;

use App\Architectures\A03_StrategyPolymorphism\Phase_04\Catalog\PricingRules;
use Illuminate\Support\Carbon;

class Phase04PricingStrategy implements PricingStrategy
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

    public function calculateEarlyBookingDiscount(float $basePrice, int $daysInAdvance): float
    {
        $rate = $this->getEarlyBookingRate($daysInAdvance);
        return $basePrice * $rate;
    }

    public function getEarlyBookingRate(int $daysInAdvance): float
    {
        if ($daysInAdvance <= 0) {
            return 0;
        }

        foreach (PricingRules::earlyBookingDiscounts() as $threshold => $percentage) {
            if ($daysInAdvance >= $threshold) {
                return $percentage;
            }
        }

        return 0;
    }

    public function calculateSeasonalSurcharge(float $basePrice, array $dates): float
    {
        $surchargeConfig = PricingRules::seasonalSurcharge();
        $highSeasonMonths = $surchargeConfig['high_season_months'];
        $surchargeRate = $surchargeConfig['surcharge_rate'];

        foreach ($dates as $date) {
            $month = (int) Carbon::parse($date)->format('n');
            if (in_array($month, $highSeasonMonths)) {
                return $basePrice * $surchargeRate;
            }
        }

        return 0;
    }
}
