<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationDecorator;

class CombinedPromoDecorator extends ReservationDecorator
{
    public function __construct(
        ReservationComponent $reservation,
        private float $discountPercentage,
        private string $reason
    ) {
        parent::__construct($reservation);
    }

    public function ownDiscountAmount(): float
    {
        return $this->reservation->basePrice() * $this->discountPercentage;
    }

    public function discountAmount(): float
    {
        return $this->reservation->discountAmount()
            + ($this->reservation->basePrice() * $this->discountPercentage);
    }

    public function discountReason(): ?string
    {
        $existing = $this->reservation->discountReason();

        return $existing ? $existing . ' + ' . $this->reason : $this->reason;
    }
}
