<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_04\Services;

use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Catalog\ProductCatalog;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\ReservationPriceBuilder;
use App\Architectures\A04_DecoratorDomain\Phase_04\Domain\Pricing\Decorators\ExtraChargeDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_04\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A04_DecoratorDomain\Phase_04\Exceptions\MinimumPriceException;

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
        $builder = new ReservationPriceBuilder();

        foreach ($productsInput as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);
            if (!$product) {
                continue;
            }

            $days = count($productInput['dates']);

            $builder->addProduct($product, $days, $productInput['dates']);

            foreach ($productInput['extras'] ?? [] as $extraInput) {
                $extra = ProductCatalog::findExtra($extraInput['extra_id']);
                if (!$extra) {
                    continue;
                }

                $builder->addExtra(
                    $this->formatExtraLineName($product, $extra),
                    $this->resolveExtraAmount($extra, $extraInput, $days)
                );
            }
        }

        return $builder->build();
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
            'base_price'      => $reservationPrice->originalBasePrice(),
            'discount_amount' => $reservationPrice->discountAmount(),
            'discount_reason' => $reservationPrice->discountReason(),
            'tax_amount'      => $reservationPrice->taxAmount(),
            'tax_rate'        => $reservationPrice->taxRate(),
            'commission_amount' => $reservationPrice->commissionAmount(),
            'early_booking_discount_amount' => 0,
            'seasonal_surcharge_amount' => 0,
        ]);

        foreach ($reservationPrice->extras() as $extraLine) {
            $this->repository->addExtra($reservation->id, $extraLine);
        }

        $this->repository->updateEarlyBookingAndSurcharge(
            $reservation->id,
            $reservationPrice->earlyBookingDiscountAmount(),
            $reservationPrice->seasonalSurchargeAmount()
        );

        return $this->repository->findWithExtras($reservation->id);
    }
}
