<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_02\Services;

use App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Catalog\ProductCatalog;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Catalog\ProductCollection;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Pricing\BasicPricingStrategy;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Domain\Reservations\MultiProductReservation;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Catalog\PricingRules;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Exceptions\MinimumPriceException;

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
        $strategy          = new BasicPricingStrategy();
        $reservationDomain = new MultiProductReservation();

        $result = $reservationDomain->calculate($validated['products'], $collection, $strategy);

        $reservation = $this->repository->create([
            'type'            => 'multi-product',
            'base_price'      => 0,
            'discount_amount' => 0,
            'discount_reason' => null,
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

        $priceAfterDiscount = $result['base_price'] - $discountAmount;
        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            throw new MinimumPriceException($priceAfterDiscount, $minimumPrice);
        }

        $discountReason = count($discountReasons) > 0 ? implode(' + ', $discountReasons) : null;

        $this->repository->updateBasePrice($reservation->id, $result['base_price']);
        $this->repository->updateDiscount($reservation->id, $discountAmount, $discountReason);

        return $this->repository->findWithExtras($reservation->id);
    }
}
