<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Reservations;

use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Pricing\PricingStrategy;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Catalog\ProductCollection;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Models\Reservation;


class MultiProductReservation extends Reservation
{
    public function calculate(
        array $productsInput,
        ProductCollection $collection,
        PricingStrategy $strategy
    ): array {
        $totalBasePrice = 0;
        $extraLines     = [];

        foreach ($productsInput as $productInput) {

            $product = $collection->getProduct($productInput['product_id']);
            if (!$product) continue;

            $days = count($productInput['dates']);

            $productPrice = $strategy->calculateProduct($product, $days);
            $totalBasePrice += $productPrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {

                $extra = $collection->getExtra($extraInput['extra_id']);
                if (!$extra) continue;

                $price = $strategy->calculateExtra(
                    $extra,
                    $days,
                    $extraInput['dates'] ?? []
                );

                $extraLines[] = [
                    'name'  => $product['name'] . ' - ' . $extra['name'],
                    'price' => $price,
                ];
            }
        }

        return [
            'base_price' => $totalBasePrice,
            'extras'     => $extraLines,
        ];
    }
}