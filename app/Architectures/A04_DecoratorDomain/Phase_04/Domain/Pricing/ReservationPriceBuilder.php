<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing;

use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\ExtraChargeDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\ProductBasePriceDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\VolumeDiscountDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\CombinedPromoDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\TaxDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\CommissionDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\EarlyBookingDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\SeasonalSurchargeDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Catalog\PricingRules;
use App\Architectures\A04_DecoratorDomain\Phase_04\Exceptions\MinimumPriceException;

class ReservationPriceBuilder
{
    private array $products = [];
    private array $extras = [];
    private int $totalNights = 0;
    private array $productIds = [];
    private array $allDates = [];

    public function addProduct(array $product, int $days, array $dates): self
    {
        $this->products[] = [
            'product' => $product,
            'days'    => $days,
            'dates'   => $dates,
        ];

        $this->totalNights += $days;
        $this->productIds[] = $product['id'];

        foreach ($dates as $date) {
            $this->allDates[] = $date;
        }

        return $this;
    }

    public function addExtra(string $name, float $amount): self
    {
        $this->extras[] = [
            'name'  => $name,
            'price' => $amount,
        ];

        return $this;
    }

    public function build(): ReservationComponent
    {
        $reservation = new BaseReservation();

        $reservation = $this->applyProducts($reservation);
        $reservation = $this->applyExtras($reservation);
        $reservation = $this->applyVolumeDiscount($reservation);
        $reservation = $this->applyCombinedPromotions($reservation);
        $reservation = $this->applyPhase04Rules($reservation);

        $this->validateMinimumPrice($reservation);

        return $reservation;
    }

    private function applyProducts(ReservationComponent $reservation): ReservationComponent
    {
        foreach ($this->products as $item) {
            $basePrice = $item['product']['price_per_night'] * $item['days'];

            $reservation = new ProductBasePriceDecorator(
                $reservation,
                $basePrice,
                $item['product']['product_type'],
                $item['dates']
            );
        }

        return $reservation;
    }

    private function applyExtras(ReservationComponent $reservation): ReservationComponent
    {
        foreach ($this->extras as $extra) {
            $reservation = new ExtraChargeDecorator(
                $reservation,
                $extra['name'],
                $extra['price']
            );
        }

        return $reservation;
    }

    private function applyVolumeDiscount(ReservationComponent $reservation): ReservationComponent
    {
        foreach (PricingRules::volumeDiscounts() as $threshold => $percentage) {
            if ($this->totalNights >= $threshold) {
                return new VolumeDiscountDecorator(
                    $reservation,
                    $percentage,
                    'volume-' . intval($percentage * 100) . '%'
                );
            }
        }

        return $reservation;
    }

    private function applyCombinedPromotions(ReservationComponent $reservation): ReservationComponent
    {
        foreach (PricingRules::combinedPromotions() as $promo) {
            $requiredProducts = $promo['products'];
            $hasAllProducts = count(array_intersect($requiredProducts, $this->productIds)) === count($requiredProducts);

            if ($hasAllProducts) {
                $reservation = new CombinedPromoDecorator(
                    $reservation,
                    $promo['discount'],
                    'combined-promo-' . intval($promo['discount'] * 100) . '%'
                );
            }
        }

        return $reservation;
    }

    private function applyPhase04Rules(ReservationComponent $reservation): ReservationComponent
    {
        $reservation = new EarlyBookingDecorator($reservation, $this->allDates);
        $reservation = new SeasonalSurchargeDecorator($reservation, $this->allDates);
        $reservation = new TaxDecorator($reservation);
        $reservation = new CommissionDecorator($reservation);

        return $reservation;
    }

    private function validateMinimumPrice(ReservationComponent $reservation): void
    {
        $priceAfterDiscount = $reservation->originalBasePrice()
            - $reservation->discountAmount()
            - $reservation->earlyBookingDiscountAmount()
            + $reservation->seasonalSurchargeAmount();

        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            throw new MinimumPriceException($priceAfterDiscount, $minimumPrice);
        }
    }
}
