<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing;

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

    public function discountAmount(): float
    {
        return 0;
    }

    public function discountReason(): ?string
    {
        return null;
    }
}
