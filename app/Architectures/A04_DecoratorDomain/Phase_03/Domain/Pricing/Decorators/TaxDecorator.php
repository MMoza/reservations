<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Catalog\PricingRules;

class TaxDecorator extends ReservationDecorator
{
    public function __construct(
        ReservationComponent $reservation
    ) {
        parent::__construct($reservation);
    }

    public function taxAmount(): float
    {
        $rate = $this->getTaxRate();
        return $this->reservation->basePrice() * $rate;
    }

    public function taxRate(): ?string
    {
        $rate = $this->getTaxRate();
        return $rate > 0 ? 'Tax ' . intval($rate * 100) . '%' : null;
    }

    private function getTaxRate(): float
    {
        $type = $this->reservation->productType();
        return PricingRules::taxRates()[$type] ?? 0;
    }
}
