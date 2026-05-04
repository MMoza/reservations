<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Services;

use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Catalog\ProductCatalog;
use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing\BaseReservation;
use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing\ReservationComponent;
use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing\Decorators\ExtraChargeDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing\Decorators\ProductBasePriceDecorator;
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;

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

        foreach ($productsInput as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);

            if (!$product) {
                continue;
            }

            $days = count($productInput['dates']);

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

        return $reservationPrice;
    }

    private function applyProductBasePrice(
        ReservationComponent $reservationPrice,
        array $product,
        int $days
    ): ReservationComponent {
        $basePrice = $product['price_per_night'] * $days;

        return new ProductBasePriceDecorator($reservationPrice, $basePrice);
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
        if ($extra['type'] !== 'per_night') {
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
            'type' => 'multi-product',
            'base_price' => $reservationPrice->basePrice(),
        ]);

        foreach ($reservationPrice->extras() as $extraLine) {
            $this->repository->addExtra($reservation->id, $extraLine);
        }

        return $this->repository->findWithExtras($reservation->id);
    }
}