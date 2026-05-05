<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Catalog\PricingRules;

class CommissionDecorator extends ReservationDecorator
{
    public function __construct(
        ReservationComponent $reservation
    ) {
        parent::__construct($reservation);
    }

    public function commissionAmount(): float
    {
        $rate = $this->getCommissionRate();
        return $this->reservation->basePrice() * $rate;
    }

    private function getCommissionRate(): float
    {
        $type = $this->reservation->productType();
        return PricingRules::commissionRates()[$type] ?? 0;
    }
}
