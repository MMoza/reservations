<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing;

abstract class ReservationDecorator implements ReservationComponent
{
    public function __construct(
        protected ReservationComponent $reservation
    ) {}

    public function basePrice(): float
    {
        return $this->reservation->basePrice();
    }

    public function extras(): array
    {
        return $this->reservation->extras();
    }

    public function total(): float
    {
        return $this->basePrice() + collect($this->extras())->sum('price');
    }
}
