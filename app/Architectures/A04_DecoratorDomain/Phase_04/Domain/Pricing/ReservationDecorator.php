<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing;

abstract class ReservationDecorator implements ReservationComponent
{
    public function __construct(
        protected ReservationComponent $reservation
    ) {}

    public function basePrice(): float
    {
        return $this->reservation->basePrice() - $this->ownDiscountAmount();
    }

    public function originalBasePrice(): float
    {
        return $this->reservation->originalBasePrice();
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
            + $this->commissionAmount()
            - $this->earlyBookingDiscountAmount()
            + $this->seasonalSurchargeAmount();
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

    public function earlyBookingDiscountAmount(): float
    {
        return $this->reservation->earlyBookingDiscountAmount();
    }

    public function seasonalSurchargeAmount(): float
    {
        return $this->reservation->seasonalSurchargeAmount();
    }

    public function allDates(): array
    {
        return $this->reservation->allDates();
    }
}
