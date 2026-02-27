<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_01\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Requests\StoreReservationRequest;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Services\CreateReservationService;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationController extends Controller
{
    public function __construct(
        private CreateReservationService $service,
        private ReservationRepositoryInterface $repository
    ) {}

    public function store(StoreReservationRequest $request)
    {
        $reservation = $this->service->execute(
            $request->validated()
        );

        return response()->json($reservation, 201);
    }

    public function show(int $id)
    {
        return response()->json(
            $this->repository->findWithExtras($id)
        );
    }
}