<?php

namespace App\Architectures\A02_RepositoryPattern\Phase_01\Services;

use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_01\Catalog\ProductCatalog;

class ReservationService
{
    public function __construct(
        private ReservationRepositoryInterface $repository
    ) {}

    public function createReservation(array $validated)
    {
        $reservation = $this->repository->create([
            'type'       => 'multi-product',
            'base_price' => 0,
        ]);

        $totalBasePrice = 0;

        foreach ($validated['products'] as $productInput) {

            $product = ProductCatalog::findProduct($productInput['product_id']);
            if (!$product) continue;

            $numberOfDays = count($productInput['dates']);
            $lineBasePrice = $product['price_per_night'] * $numberOfDays;
            $totalBasePrice += $lineBasePrice;

            foreach ($productInput['extras'] ?? [] as $extraInput) {

                $extra = ProductCatalog::findExtra($extraInput['extra_id']);
                if (!$extra) continue;

                if ($extra['type'] === 'per_night') {
                    $daysApplied = empty($extraInput['dates'])
                        ? $numberOfDays
                        : count($extraInput['dates']);
                    $price = $extra['price'] * $daysApplied;
                } else {
                    $price = $extra['price'];
                }

                $this->repository->addExtra($reservation->id, [
                    'name'  => $product['name'].' - '.$extra['name'],
                    'price' => $price,
                ]);
            }
        }

        $this->repository->updateBasePrice($reservation->id, $totalBasePrice);

        return $this->repository->findWithExtras($reservation->id);
    }

    public function getById(int $id)
    {
        return $this->repository->findWithExtras($id);
    }
}