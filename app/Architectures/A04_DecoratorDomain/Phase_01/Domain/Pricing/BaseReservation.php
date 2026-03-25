<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing;

class BaseReservation implements ReservationComponent
{
    public function basePrice(): float
    {
        return 0;
    }

    public function extras(): array
    {
        return [];
    }

    public function total(): float
    {
        return 0;
    }
}
