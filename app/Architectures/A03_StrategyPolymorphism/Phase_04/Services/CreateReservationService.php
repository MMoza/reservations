<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_04\Services;

use App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Catalog\ProductCatalog;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Catalog\ProductCollection;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Pricing\Phase04PricingStrategy;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Domain\Reservations\MultiProductReservation;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Catalog\PricingRules;
use App\Architectures\A03_StrategyPolymorphism\Phase_04\Exceptions\MinimumPriceException;
use Illuminate\Support\Carbon;

class CreateReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $repository
    ) {}

    public function execute(array $validated)
    {
        $productIds = collect($validated['products'])
            ->pluck('product_id')
            ->unique()
            ->toArray();

        $extraIds = collect($validated['products'])
            ->flatMap(fn ($product) => $product['extras'] ?? [])
            ->pluck('extra_id')
            ->unique()
            ->toArray();

        $products          = ProductCatalog::findProductsByIds($productIds);
        $extras            = ProductCatalog::findExtrasByIds($extraIds);
        $collection        = new ProductCollection($products, $extras);
        $strategy          = new Phase04PricingStrategy();
        $reservationDomain = new MultiProductReservation();

        $result = $reservationDomain->calculate($validated['products'], $collection, $strategy);

        $reservation = $this->repository->create([
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

        foreach ($result['extras'] as $line) {
            $this->repository->addExtra($reservation->id, $line);
        }

        $discountAmount = 0;
        $discountReasons = [];

        foreach (PricingRules::volumeDiscounts() as $threshold => $percentage) {
            if ($result['total_nights'] >= $threshold) {
                $discount = $result['base_price'] * $percentage;
                $discountAmount += $discount;
                $discountReasons[] = 'volume-' . intval($percentage * 100) . '%';
                break;
            }
        }

        foreach (PricingRules::combinedPromotions() as $promo) {
            $requiredProducts = $promo['products'];
            $hasAllProducts = count(array_intersect($requiredProducts, $result['product_ids'])) === count($requiredProducts);

            if ($hasAllProducts) {
                $discount = $result['base_price'] * $promo['discount'];
                $discountAmount += $discount;
                $discountReasons[] = 'combined-promo-' . intval($promo['discount'] * 100) . '%';
            }
        }

        $firstDate = collect($result['all_dates'])->sort()->first();
        $daysInAdvance = now()->diffInDays(Carbon::parse($firstDate), false);

        $earlyBookingDiscountAmount = $strategy->calculateEarlyBookingDiscount($result['base_price'], $daysInAdvance);
        if ($earlyBookingDiscountAmount > 0) {
            $rate = $strategy->getEarlyBookingRate($daysInAdvance);
            $discountAmount += $earlyBookingDiscountAmount;
            $discountReasons[] = 'early-booking-' . intval($rate * 100) . '%';
        }

        $priceAfterDiscount = $result['base_price'] - $discountAmount;
        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            throw new MinimumPriceException($priceAfterDiscount, $minimumPrice);
        }

        $discountReason = count($discountReasons) > 0 ? implode(' + ', $discountReasons) : null;

        $primaryType = $result['primary_type'];

        $taxAmount = $strategy->calculateTax($primaryType, $result['base_price']);
        $taxRate = $strategy->getTaxRate($primaryType);
        $commissionAmount = $strategy->calculateCommission($primaryType, $result['base_price']);

        $seasonalSurchargeAmount = $strategy->calculateSeasonalSurcharge($result['base_price'], $result['all_dates']);

        $this->repository->updateBasePrice($reservation->id, $result['base_price']);
        $this->repository->updateDiscount($reservation->id, $discountAmount, $discountReason);
        $this->repository->updateTaxesAndCommission(
            $reservation->id,
            $taxAmount,
            $taxRate > 0 ? 'Tax ' . intval($taxRate * 100) . '%' : null,
            $commissionAmount
        );
        $this->repository->updateEarlyBookingAndSurcharge(
            $reservation->id,
            $earlyBookingDiscountAmount,
            $seasonalSurchargeAmount
        );

        return $this->repository->findWithExtras($reservation->id);
    }
}
