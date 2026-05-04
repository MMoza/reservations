<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_02\Domain\Pricing\ReservationDecorator;

class ExtraChargeDecorator extends ReservationDecorator
{
    public function __construct(ReservationComponent $reservation, private string $name, private float $amount)
    {
        parent::__construct($reservation);
    }

    public function extras(): array
    {
        return [
            ...$this->reservation->extras(),
            [
                'name' => $this->name,
                'price' => $this->amount,
            ],
        ];
    }
}
