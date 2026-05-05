<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators;

use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Catalog\PricingRules;
use Illuminate\Support\Carbon;

class EarlyBookingDecorator extends ReservationDecorator
{
    private float $discountAmount = 0;
    private string $reason = '';

    public function __construct(
        ReservationComponent $reservation,
        array $dates
    ) {
        parent::__construct($reservation);

        $this->applyEarlyBookingDiscount($dates);
    }

    private function applyEarlyBookingDiscount(array $dates): void
    {
        $firstDate = collect($dates)->sort()->first();
        if (!$firstDate) {
            return;
        }

        $daysInAdvance = now()->diffInDays(Carbon::parse($firstDate), false);

        if ($daysInAdvance <= 0) {
            return;
        }

        foreach (PricingRules::earlyBookingDiscounts() as $threshold => $percentage) {
            if ($daysInAdvance >= $threshold) {
                $this->discountAmount = $this->reservation->originalBasePrice() * $percentage;
                $this->reason = 'early-booking-' . intval($percentage * 100) . '%';
                break;
            }
        }
    }

    public function ownDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function discountAmount(): float
    {
        return $this->reservation->discountAmount() + $this->discountAmount;
    }

    public function discountReason(): ?string
    {
        if (empty($this->reason)) {
            return $this->reservation->discountReason();
        }

        $existing = $this->reservation->discountReason();
        return $existing ? $existing . ' + ' . $this->reason : $this->reason;
    }

    public function earlyBookingDiscountAmount(): float
    {
        return $this->discountAmount;
    }
}
