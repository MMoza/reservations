<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_03\Services;

use App\Architectures\A02_RepositoryPattern\Phase_03\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_03\Catalog\ProductCatalog;
use App\Architectures\A02_RepositoryPattern\Phase_03\Catalog\PricingRules;
use App\Architectures\A02_RepositoryPattern\Phase_03\Exceptions\MinimumPriceException;

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
            'tax_amount'      => 0,
            'tax_rate'        => null,
            'commission_amount' => 0,
        ]);

        $totalBasePrice = 0;
        $totalNights    = 0;
        $productIds     = [];
        $productTypes   = [];

        foreach ($validated['products'] as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);
            if (!$product) continue;

            $productIds[] = $productInput['product_id'];
            $productTypes[] = $product['product_type'];

            $numberOfDays = count($productInput['dates']);
            $totalNights += $numberOfDays;

            $lineBasePrice = $product['price_per_night'] * $numberOfDays;
            $totalBasePrice += $lineBasePrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {
                $extra = ProductCatalog::findExtra($extraInput['extra_id']);
                if (!$extra) continue;

                $extraDates = $extraInput['dates'] ?? [];

                if ($extra['charge_type'] === 'per_night') {
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

        $primaryType = count($productTypes) > 0 ? reset($productTypes) : 'hotel';

        $taxRate = PricingRules::taxRates()[$primaryType] ?? 0;
        $taxAmount = $totalBasePrice * $taxRate;

        $commissionAmount = 0;
        if (isset(PricingRules::commissionRates()[$primaryType])) {
            $commissionAmount = $totalBasePrice * PricingRules::commissionRates()[$primaryType];
        }

        $this->repository->updateBasePrice($reservation->id, $totalBasePrice);
        $this->repository->updateDiscount($reservation->id, $discountAmount, $discountReason);
        $this->repository->updateTaxesAndCommission(
            $reservation->id,
            $taxAmount,
            $taxRate > 0 ? 'Tax ' . intval($taxRate * 100) . '%' : null,
            $commissionAmount
        );

        return $this->repository->findWithExtras($reservation->id);
    }

    public function getById(int $id)
    {
        return $this->repository->findWithExtras($id);
    }
}
