<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_02\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);

    public function addExtra(int $reservationId, array $extraData);

    public function updateBasePrice(int $reservationId, float $basePrice);

    public function updateDiscount(int $reservationId, float $amount, ?string $reason);

    public function findWithExtras(int $reservationId);
}
