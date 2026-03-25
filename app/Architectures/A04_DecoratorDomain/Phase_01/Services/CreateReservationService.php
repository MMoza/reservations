<?php

namespace App\Architectures\A04_DecoratorDomain\Phase_01\Services;

use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Catalog\ProductCatalog;
use App\Architectures\A04_DecoratorDomain\Phase_01\Domain\Pricing\BaseReservation;
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
        $reservationPrice = new BaseReservation();

        foreach ($validated['products'] as $productInput) {
            $product = ProductCatalog::findProduct($productInput['product_id']);

            if (!$product) {
                continue;
            }

            $days = count($productInput['dates']);
            $basePrice = $product['price_per_night'] * $days;

            $reservationPrice = new ProductBasePriceDecorator(
                $reservationPrice,
                $basePrice
            );

            foreach ($productInput['extras'] ?? [] as $extraInput) {
                $extra = ProductCatalog::findExtra($extraInput['extra_id']);

                if (!$extra) {
                    continue;
                }

                $daysApplied = empty($extraInput['dates'])
                    ? $days
                    : count($extraInput['dates']);

                $amount = $extra['type'] === 'per_night'
                    ? $extra['price'] * $daysApplied
                    : $extra['price'];

                $reservationPrice = new ExtraChargeDecorator(
                    $reservationPrice,
                    $product['name'] . ' - ' . $extra['name'],
                    $amount
                );
            }
        }

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
