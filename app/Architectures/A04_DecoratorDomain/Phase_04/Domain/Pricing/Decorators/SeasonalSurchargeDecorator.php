<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Catalog\PricingRules;
use Illuminate\Support\Carbon;

class SeasonalSurchargeDecorator extends ReservationDecorator
{
    private float $surchargeAmount = 0;

    public function __construct(
        ReservationComponent $reservation,
        array $dates
    ) {
        parent::__construct($reservation);

        $this->applySeasonalSurcharge($dates);
    }

    private function applySeasonalSurcharge(array $dates): void
    {
        $config = PricingRules::seasonalSurcharge();
        $highSeasonMonths = $config['high_season_months'];
        $surchargeRate = $config['surcharge_rate'];

        foreach ($dates as $date) {
            $month = (int) Carbon::parse($date)->format('n');
            if (in_array($month, $highSeasonMonths)) {
                $this->surchargeAmount = $this->reservation->originalBasePrice() * $surchargeRate;
                break;
            }
        }
    }

    public function seasonalSurchargeAmount(): float
    {
        return $this->surchargeAmount;
    }

    public function total(): float
    {
        return $this->basePrice()
            + collect($this->extras())->sum('price')
            + $this->taxAmount()
            + $this->commissionAmount()
            - $this->earlyBookingDiscountAmount()
            + $this->surchargeAmount;
    }
}
