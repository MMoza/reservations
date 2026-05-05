<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing;

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

    public function taxAmount(): float
    {
        return 0;
    }

    public function taxRate(): ?string
    {
        return null;
    }

    public function commissionAmount(): float
    {
        return 0;
    }

    public function productType(): ?string
    {
        return null;
    }
}
