<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_03\Repositories\Eloquent;

use App\Architectures\A03_StrategyPolymorphism\Phase_03\Models\Reservation;
use App\Architectures\A03_StrategyPolymorphism\Phase_03\Repositories\Contracts\ReservationRepositoryInterface;

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

    public function updateTaxesAndCommission(
        int $reservationId,
        float $taxAmount,
        ?string $taxRate,
        float $commissionAmount
    ) {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update([
            'tax_amount'        => $taxAmount,
            'tax_rate'          => $taxRate,
            'commission_amount' => $commissionAmount,
        ]);
    }

    public function findWithExtras(int $id)
    {
        return Reservation::with('extras')->findOrFail($id);
    }
}
