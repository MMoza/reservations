<?php

namespace App\Architectures\A01_MonolithicEloquent\Phase_04\Controllers;

use App\Http\Controllers\Controller;
use App\Architectures\A01_MonolithicEloquent\Phase_04\Requests\StoreReservationRequest;
use App\Architectures\A01_MonolithicEloquent\Phase_04\Models\Reservation;
use App\Architectures\A01_MonolithicEloquent\Phase_04\Catalog\ProductCatalog;
use App\Architectures\A01_MonolithicEloquent\Phase_04\Catalog\PricingRules;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request)
    {
        $validated = $request->validated();

        $reservation = Reservation::create([
            'type'            => 'multi-product',
            'base_price'      => 0,
            'discount_amount' => 0,
            'discount_reason' => null,
            'tax_amount'      => 0,
            'tax_rate'        => null,
            'commission_amount' => 0,
            'early_booking_discount_amount' => 0,
            'seasonal_surcharge_amount' => 0,
        ]);

        $totalBasePrice = 0;
        $totalNights    = 0;
        $productIds     = [];
        $productTypes   = [];
        $allDates       = [];

        foreach ($validated['products'] as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);

            if (!$product) {
                continue;
            }

            $productIds[] = $productInput['product_id'];
            $productTypes[] = $product['product_type'];

            $reservationDates = collect($productInput['dates']);
            $numberOfDays     = $reservationDates->count();
            $totalNights     += $numberOfDays;

            foreach ($productInput['dates'] as $date) {
                $allDates[] = $date;
            }

            $lineBasePrice   = $product['price_per_night'] * $numberOfDays;
            $totalBasePrice += $lineBasePrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {
                $extra = ProductCatalog::findExtra($extraInput['extra_id']);

                if (!$extra) {
                    continue;
                }

                $extraDates = collect($extraInput['dates'] ?? []);

                if ($extra['charge_type'] === 'per_night') {
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

        $discountAmount = 0;
        $discountReasons = [];

        foreach (PricingRules::volumeDiscounts() as $threshold => $percentage) {
            if ($totalNights >= $threshold) {
                $discount = $totalBasePrice * $percentage;
                $discountAmount += $discount;
                $discountReasons[] = 'volume-' . intval($percentage * 100) . '%';
                break;
            }
        }

        foreach (PricingRules::combinedPromotions() as $promo) {
            $requiredProducts = $promo['products'];
            $hasAllProducts = count(array_intersect($requiredProducts, $productIds)) === count($requiredProducts);

            if ($hasAllProducts) {
                $discount = $totalBasePrice * $promo['discount'];
                $discountAmount += $discount;
                $discountReasons[] = 'combined-promo-' . intval($promo['discount'] * 100) . '%';
            }
        }

        $firstDate = collect($allDates)->sort()->first();
        $daysInAdvance = now()->diffInDays(\Illuminate\Support\Carbon::parse($firstDate), false);

        if ($daysInAdvance > 0) {
            foreach (PricingRules::earlyBookingDiscounts() as $threshold => $percentage) {
                if ($daysInAdvance >= $threshold) {
                    $earlyBookingDiscount = $totalBasePrice * $percentage;
                    $discountAmount += $earlyBookingDiscount;
                    $discountReasons[] = 'early-booking-' . intval($percentage * 100) . '%';
                    break;
                }
            }
        }

        $priceAfterDiscount = $totalBasePrice - $discountAmount;

        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            return response()->json([
                'message' => 'The discounted price (' . number_format($priceAfterDiscount, 2) . ') is below the minimum guaranteed price of ' . number_format($minimumPrice, 2) . '.',
            ], 422);
        }

        $discountReason = count($discountReasons) > 0 ? implode(' + ', $discountReasons) : null;

        $highSeasonMonths = PricingRules::seasonalSurcharge()['high_season_months'];
        $surchargeRate = PricingRules::seasonalSurcharge()['surcharge_rate'];

        $seasonalSurchargeAmount = 0;
        foreach ($allDates as $date) {
            $month = (int) \Illuminate\Support\Carbon::parse($date)->format('n');
            if (in_array($month, $highSeasonMonths)) {
                $seasonalSurchargeAmount = $totalBasePrice * $surchargeRate;
                break;
            }
        }

        $primaryType = count($productTypes) > 0 ? reset($productTypes) : 'hotel';

        $taxRate = PricingRules::taxRates()[$primaryType] ?? 0;
        $taxAmount = $totalBasePrice * $taxRate;

        $commissionAmount = 0;
        if (isset(PricingRules::commissionRates()[$primaryType])) {
            $commissionAmount = $totalBasePrice * PricingRules::commissionRates()[$primaryType];
        }

        $earlyBookingDiscountAmount = 0;
        foreach ($discountReasons as $reason) {
            if (str_starts_with($reason, 'early-booking-')) {
                $earlyBookingDiscountAmount = $totalBasePrice * (intval(explode('-', $reason)[2]) / 100);
                break;
            }
        }

        $reservation->update([
            'base_price'      => $totalBasePrice,
            'discount_amount' => $discountAmount,
            'discount_reason' => $discountReason,
            'tax_amount'      => $taxAmount,
            'tax_rate'        => $taxRate > 0 ? 'Tax ' . intval($taxRate * 100) . '%' : null,
            'commission_amount' => $commissionAmount,
            'early_booking_discount_amount' => $earlyBookingDiscountAmount,
            'seasonal_surcharge_amount' => $seasonalSurchargeAmount,
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
