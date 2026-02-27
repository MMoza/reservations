<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_01\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A02_RepositoryPattern\Phase_01\Services\ReservationService;
use App\Architectures\A02_RepositoryPattern\Phase_01\Requests\StoreReservationRequest;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $service
    ) {}

    public function store(StoreReservationRequest $request)
    {
        $reservation = $this->service->createReservation(
            $request->validated()
        );

        return response()->json($reservation, 201);
    }

    public function show($id)
    {
        return response()->json(
            $this->service->getById($id)
        );
    }
}