<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing;

interface ReservationComponent
{
    public function basePrice(): float;

    public function extras(): array;

    public function total(): float;

    public function discountAmount(): float;

    public function discountReason(): ?string;
}
