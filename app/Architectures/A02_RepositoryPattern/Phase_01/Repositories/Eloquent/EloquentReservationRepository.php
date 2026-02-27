<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Eloquent;

use App\Architectures\A02_RepositoryPattern\Phase_01\Models\Reservation;
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;

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

    public function findWithExtras(int $id)
    {
        return Reservation::with('extras')->findOrFail($id);
    }
}