<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);

    public function addExtra(int $reservationId, array $extraData);

    public function updateEarlyBookingAndSurcharge(
        int $reservationId,
        float $earlyBookingDiscountAmount,
        float $seasonalSurchargeAmount
    );

    public function findWithExtras(int $reservationId);
}
