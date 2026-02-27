<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_01\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A01_MonolithicEloquent\Phase_01\Requests\StoreReservationRequest;
use App\Architectures\A01_MonolithicEloquent\Phase_01\Models\Reservation;
use App\Architectures\A01_MonolithicEloquent\Phase_01\Catalog\ProductCatalog;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request)
    {
        $validated = $request->validated();

        $reservation = Reservation::create([
            'type'       => 'multi-product',
            'base_price' => 0,
        ]);

        $totalBasePrice = 0;

        foreach ($validated['products'] as $productInput) {

            $product = ProductCatalog::findProduct($productInput['product_id']);

            if (!$product) {
                continue;
            }

            $reservationDates = collect($productInput['dates']);
            $numberOfDays     = $reservationDates->count();

            $lineBasePrice   = $product['price_per_night'] * $numberOfDays;
            $totalBasePrice += $lineBasePrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {

                $extra = ProductCatalog::findExtra($extraInput['extra_id']);

                if (!$extra) {
                    continue;
                }

                $extraDates = collect($extraInput['dates'] ?? []);

                if ($extra['type'] === 'per_night') {

                    $daysApplied = $extraDates->isEmpty()
                        ? $numberOfDays
                        : $extraDates->count();

                    $price = $extra['price'] * $daysApplied;

                } else {

                    $price = $extra['price'];
                }

                $reservation->extras()->create([
                    'name'  => $product['name'] . ' - ' . $extra['name'],
                    'price' => $price,
                ]);
            }
        }

        // Actualizar base_price acumulado
        $reservation->update([
            'base_price' => $totalBasePrice,
        ]);

        return response()->json(
            $reservation->load('extras'),
            201
        );
    }

    public function show($id)
    {
        $reservation = Reservation::with('extras')->findOrFail($id);

        return response()->json($reservation);
    }
}