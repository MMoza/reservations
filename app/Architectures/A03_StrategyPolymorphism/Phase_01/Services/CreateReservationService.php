<?php

namespace App\Architectures\A03_StrategyPolymorphism\Phase_01\Services;

use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Catalog\ProductCatalog;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Catalog\ProductCollection;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Pricing\BasicPricingStrategy;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Domain\Reservations\MultiProductReservation;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts\ReservationRepositoryInterface;

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
            'type'       => 'multi-product',
            'base_price' => $result['base_price'],
        ]);

        foreach ($result['extras'] as $line) {
            $this->repository->addExtra($reservation->id, $line);
        };
    
        return $this->repository->findWithExtras($reservation->id);
    }
}