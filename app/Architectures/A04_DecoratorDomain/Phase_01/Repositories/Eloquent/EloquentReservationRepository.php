<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Eloquent;

use App\Architectures\A04_DecoratorDomain\Phase_01\Models\Reservation;
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;

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

    public function findWithExtras(int $reservationId)
    {
        return Reservation::with('extras')->findOrFail($reservationId);
    }
}
