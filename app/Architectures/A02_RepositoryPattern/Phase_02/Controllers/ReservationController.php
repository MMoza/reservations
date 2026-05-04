<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_02\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A02_RepositoryPattern\Phase_02\Services\ReservationService;
use App\Architectures\A02_RepositoryPattern\Phase_02\Requests\StoreReservationRequest;
use App\Architectures\A02_RepositoryPattern\Phase_02\Exceptions\MinimumPriceException;

class ReservationController extends Controller
{
    public function __construct(
        private ReservationService $service
    ) {}

    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = $this->service->createReservation($request->validated());
            return response()->json($reservation, 201);
        } catch (MinimumPriceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        return response()->json($this->service->getById($id));
    }
}
