<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing;

interface ReservationComponent
{
    public function basePrice(): float;

    public function originalBasePrice(): float;

    public function extras(): array;

    public function total(): float;

    public function discountAmount(): float;

    public function discountReason(): ?string;

    public function taxAmount(): float;

    public function taxRate(): ?string;

    public function commissionAmount(): float;

    public function productType(): ?string;

    public function earlyBookingDiscountAmount(): float;

    public function seasonalSurchargeAmount(): float;

    public function allDates(): array;
}
