<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationDecorator;

class ProductBasePriceDecorator extends ReservationDecorator
{
    public function __construct(
        ReservationComponent $reservation,
        private float $amount,
        private ?string $productType = null,
        private array $dates = []
    ) {
        parent::__construct($reservation);
    }

    public function basePrice(): float
    {
        return $this->reservation->basePrice() + $this->amount;
    }

    public function originalBasePrice(): float
    {
        return $this->reservation->originalBasePrice() + $this->amount;
    }

    public function productType(): ?string
    {
        return $this->productType ?? $this->reservation->productType();
    }

    public function allDates(): array
    {
        return array_merge($this->reservation->allDates(), $this->dates);
    }
}
