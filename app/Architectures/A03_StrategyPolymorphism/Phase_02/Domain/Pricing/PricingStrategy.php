<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Pricing;

interface PricingStrategy
{
    public function calculateProduct(array $product, int $days): float;

    public function calculateExtra(array $extra, int $days, array $extraDates): float;
}
