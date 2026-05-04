<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_02\Services;

use App\Architectures\A02_RepositoryPattern\Phase_02\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_02\Catalog\ProductCatalog;
use App\Architectures\A02_RepositoryPattern\Phase_02\Catalog\PricingRules;
use App\Architectures\A02_RepositoryPattern\Phase_02\Exceptions\MinimumPriceException;

class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $repository
    ) {}

    public function createReservation(array $validated)
    {
        $reservation = $this->repository->create([
            'type'            => 'multi-product',
            'base_price'      => 0,
            'discount_amount' => 0,
            'discount_reason' => null,
        ]);

        $totalBasePrice = 0;
        $totalNights    = 0;
        $productIds     = [];

        foreach ($validated['products'] as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);
            if (!$product) continue;

            $productIds[] = $productInput['product_id'];

            $numberOfDays = count($productInput['dates']);
            $totalNights += $numberOfDays;

            $lineBasePrice = $product['price_per_night'] * $numberOfDays;
            $totalBasePrice += $lineBasePrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {
                $extra = ProductCatalog::findExtra($extraInput['extra_id']);
                if (!$extra) continue;

                $extraDates = $extraInput['dates'] ?? [];

                if ($extra['type'] === 'per_night') {
                    $daysApplied = empty($extraDates)
                        ? $numberOfDays
                        : count($extraDates);
                    $price = $extra['price'] * $daysApplied;
                } else {
                    $price = $extra['price'];
                }

                $this->repository->addExtra($reservation->id, [
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

        $priceAfterDiscount = $totalBasePrice - $discountAmount;
        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            throw new MinimumPriceException($priceAfterDiscount, $minimumPrice);
        }

        $discountReason = count($discountReasons) > 0 ? implode(' + ', $discountReasons) : null;

        $this->repository->updateBasePrice($reservation->id, $totalBasePrice);
        $this->repository->updateDiscount($reservation->id, $discountAmount, $discountReason);

        return $this->repository->findWithExtras($reservation->id);
    }

    public function getById(int $id)
    {
        return $this->repository->findWithExtras($id);
    }
}
