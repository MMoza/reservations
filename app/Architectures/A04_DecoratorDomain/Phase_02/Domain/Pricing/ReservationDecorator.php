<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing;

abstract class ReservationDecorator implements ReservationComponent
{
    public function __construct(
        protected ReservationComponent $reservation
    ) {}

    public function basePrice(): float
    {
        return $this->reservation->basePrice() - $this->ownDiscountAmount();
    }

    public function ownDiscountAmount(): float
    {
        return 0;
    }

    public function extras(): array
    {
        return $this->reservation->extras();
    }

    public function total(): float
    {
        return $this->basePrice() + collect($this->extras())->sum('price');
    }

    public function discountAmount(): float
    {
        return $this->reservation->discountAmount();
    }

    public function discountReason(): ?string
    {
        return $this->reservation->discountReason();
    }
}
