<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing;

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
        return $this->basePrice()
            + collect($this->extras())->sum('price')
            + $this->taxAmount()
            + $this->commissionAmount();
    }

    public function discountAmount(): float
    {
        return $this->reservation->discountAmount();
    }

    public function discountReason(): ?string
    {
        return $this->reservation->discountReason();
    }

    public function taxAmount(): float
    {
        return $this->reservation->taxAmount();
    }

    public function taxRate(): ?string
    {
        return $this->reservation->taxRate();
    }

    public function commissionAmount(): float
    {
        return $this->reservation->commissionAmount();
    }

    public function productType(): ?string
    {
        return $this->reservation->productType();
    }
}
