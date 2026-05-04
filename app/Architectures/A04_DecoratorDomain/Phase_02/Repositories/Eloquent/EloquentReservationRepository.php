<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_02\Repositories\Eloquent;

use App\Architectures\A04_DecoratorDomain\Phase_02\Models\Reservation;
use App\Architectures\A04_DecoratorDomain\Phase_02\Repositories\Contracts\ReservationRepositoryInterface;

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

    public function updateBasePrice(int $reservationId, float $basePrice)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update(['base_price' => $basePrice]);
    }

    public function updateDiscount(int $reservationId, float $amount, ?string $reason)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update([
            'discount_amount' => $amount,
            'discount_reason' => $reason,
        ]);
    }

    public function findWithExtras(int $id)
    {
        return Reservation::with('extras')->findOrFail($id);
    }
}
