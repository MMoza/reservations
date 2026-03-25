<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);

    public function addExtra(int $reservationId, array $extraData);

    public function findWithExtras(int $reservationId);
}
