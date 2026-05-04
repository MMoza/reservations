<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\ReservationDecorator;

class ProductBasePriceDecorator extends ReservationDecorator
{
    public function __construct(ReservationComponent $reservation, private float $amount)
    {
        parent::__construct($reservation);
    }

    public function basePrice(): float
    {
        return $this->reservation->basePrice() + $this->amount;
    }
}
