<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Requests\StoreReservationRequest;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Services\CreateReservationService;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Exceptions\MinimumPriceException;

class ReservationController extends Controller
{
    public function __construct(
        private CreateReservationService $service,
        private ReservationRepositoryInterface $repository
    ) {}

    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = $this->service->execute($request->validated());
            return response()->json($reservation, 201);
        } catch (MinimumPriceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id)
    {
        return response()->json($this->repository->findWithExtras($id));
    }
}
