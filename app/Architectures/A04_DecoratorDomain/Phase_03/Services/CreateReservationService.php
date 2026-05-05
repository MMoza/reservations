<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_03\Services;

use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Catalog\ProductCatalog;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\BaseReservation;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\ExtraChargeDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\ProductBasePriceDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\VolumeDiscountDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\CombinedPromoDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\TaxDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Domain\Pricing\Decorators\CommissionDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_03\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A04_DecoratorDomain\Phase_03\Catalog\PricingRules;
use App\Architectures\A04_DecoratorDomain\Phase_03\Exceptions\MinimumPriceException;

class CreateReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $repository
    ) {}

    public function execute(array $validated)
    {
        $reservationPrice = $this->buildReservationPrice($validated['products']);

        return $this->persistReservation($reservationPrice);
    }

    private function buildReservationPrice(array $productsInput): ReservationComponent
    {
        $reservationPrice = new BaseReservation();

        $totalNights = 0;
        $productIds  = [];

        foreach ($productsInput as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);

            if (!$product) {
                continue;
            }

            $productIds[] = $productInput['product_id'];

            $days = count($productInput['dates']);
            $totalNights += $days;

            $reservationPrice = $this->applyProductBasePrice(
                $reservationPrice,
                $product,
                $days
            );

            $reservationPrice = $this->applyExtraCharges(
                $reservationPrice,
                $product,
                $productInput['extras'] ?? [],
                $days
            );
        }

        foreach (PricingRules::volumeDiscounts() as $threshold => $percentage) {
            if ($totalNights >= $threshold) {
                $reservationPrice = new VolumeDiscountDecorator(
                    $reservationPrice,
                    $percentage,
                    'volume-' . intval($percentage * 100) . '%'
                );
                break;
            }
        }

        foreach (PricingRules::combinedPromotions() as $promo) {
            $requiredProducts = $promo['products'];
            $hasAllProducts = count(array_intersect($requiredProducts, $productIds)) === count($requiredProducts);

            if ($hasAllProducts) {
                $reservationPrice = new CombinedPromoDecorator(
                    $reservationPrice,
                    $promo['discount'],
                    'combined-promo-' . intval($promo['discount'] * 100) . '%'
                );
            }
        }

        $reservationPrice = new TaxDecorator($reservationPrice);
        $reservationPrice = new CommissionDecorator($reservationPrice);

        $priceAfterDiscount = $reservationPrice->basePrice();
        $minimumPrice = PricingRules::minimumPrice();

        if ($priceAfterDiscount < $minimumPrice) {
            throw new MinimumPriceException($priceAfterDiscount, $minimumPrice);
        }

        return $reservationPrice;
    }

    private function applyProductBasePrice(
        ReservationComponent $reservationPrice,
        array $product,
        int $days
    ): ReservationComponent {
        $basePrice = $product['price_per_night'] * $days;

        return new ProductBasePriceDecorator(
            $reservationPrice,
            $basePrice,
            $product['product_type']
        );
    }

    private function applyExtraCharges(
        ReservationComponent $reservationPrice,
        array $product,
        array $extrasInput,
        int $days
    ): ReservationComponent {
        foreach ($extrasInput as $extraInput) {
            $extra = ProductCatalog::findExtra($extraInput['extra_id']);

            if (!$extra) {
                continue;
            }

            $reservationPrice = new ExtraChargeDecorator(
                $reservationPrice,
                $this->formatExtraLineName($product, $extra),
                $this->resolveExtraAmount($extra, $extraInput, $days)
            );
        }

        return $reservationPrice;
    }

    private function resolveExtraAmount(array $extra, array $extraInput, int $days): float
    {
        if ($extra['charge_type'] !== 'per_night') {
            return $extra['price'];
        }

        $daysApplied = empty($extraInput['dates'])
            ? $days
            : count($extraInput['dates']);

        return $extra['price'] * $daysApplied;
    }

    private function formatExtraLineName(array $product, array $extra): string
    {
        return $product['name'] . ' - ' . $extra['name'];
    }

    private function persistReservation(ReservationComponent $reservationPrice)
    {
        $reservation = $this->repository->create([
            'type'            => 'multi-product',
            'base_price'      => $reservationPrice->basePrice(),
            'discount_amount' => $reservationPrice->discountAmount(),
            'discount_reason' => $reservationPrice->discountReason(),
            'tax_amount'      => $reservationPrice->taxAmount(),
            'tax_rate'        => $reservationPrice->taxRate(),
            'commission_amount' => $reservationPrice->commissionAmount(),
        ]);

        foreach ($reservationPrice->extras() as $extraLine) {
            $this->repository->addExtra($reservation->id, $extraLine);
        }

        return $this->repository->findWithExtras($reservation->id);
    }
}
