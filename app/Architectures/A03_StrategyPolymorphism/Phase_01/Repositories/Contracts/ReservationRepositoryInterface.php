<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts;

interface ReservationRepositoryInterface
{
    public function create(array $data);

    public function addExtra(int $reservationId, array $extraData);
    
    public function findWithExtras(int $id);
}