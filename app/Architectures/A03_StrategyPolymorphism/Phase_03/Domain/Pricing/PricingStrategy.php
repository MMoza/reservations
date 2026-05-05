<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_03\Domain\Pricing;

interface PricingStrategy
{
    public function calculateProduct(array $product, int $days): float;

    public function calculateExtra(array $extra, int $days, array $extraDates): float;

    public function calculateTax(string $productType, float $basePrice): float;

    public function calculateCommission(string $productType, float $basePrice): float;

    public function getTaxRate(string $productType): float;

    public function getCommissionRate(string $productType): float;
}
