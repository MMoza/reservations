<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Repositories\Eloquent;

use App\Architectures\A04_DecoratorDomain\Phase_04\Models\Reservation;
use App\Architectures\A04_DecoratorDomain\Phase_04\Repositories\Contracts\ReservationRepositoryInterface;

class EloquentReservationRepository implements ReservationRepositoryInterface
{
    public function create(array $data)
    {
        return Reservation::create($data);
    }

    public function addExtra(int $reservationId, array $extraData)
    {
        $reservation = Reservation::findOrFail($reservationId);
        return $reservation->extras()->create($extraData);
    }

    public function updateEarlyBookingAndSurcharge(
        int $reservationId,
        float $earlyBookingDiscountAmount,
        float $seasonalSurchargeAmount
    ) {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update([
            'early_booking_discount_amount' => $earlyBookingDiscountAmount,
            'seasonal_surcharge_amount'     => $seasonalSurchargeAmount,
        ]);
    }

    public function findWithExtras(int $id)
    {
        return Reservation::with('extras')->findOrFail($id);
    }
}
