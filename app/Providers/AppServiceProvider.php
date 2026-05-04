<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// A02 Phase 01
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A02Phase01RepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A02Phase01Repository;

// A02 Phase 02
use App\Architectures\A02_RepositoryPattern\Phase_02\Repositories\Contracts\ReservationRepositoryInterface as A02Phase02RepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_02\Repositories\Eloquent\EloquentReservationRepository as A02Phase02Repository;

// A03 Phase 01
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A03Phase01RepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A03Phase01Repository;

// A03 Phase 02
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Repositories\Contracts\ReservationRepositoryInterface as A03Phase02RepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Repositories\Eloquent\EloquentReservationRepository as A03Phase02Repository;

// A04
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A04ReservationRepositoryInterface;
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A04EloquentReservationRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // A02 Phase 01 Binding
        $this->app->bind(
            A02Phase01RepositoryInterface::class,
            A02Phase01Repository::class
        );

        // A02 Phase 02 Binding
        $this->app->bind(
            A02Phase02RepositoryInterface::class,
            A02Phase02Repository::class
        );

        // A03 Phase 01 Binding
        $this->app->bind(
            A03Phase01RepositoryInterface::class,
            A03Phase01Repository::class
        );

        // A03 Phase 02 Binding
        $this->app->bind(
            A03Phase02RepositoryInterface::class,
            A03Phase02Repository::class
        );

        // A04 Binding
        $this->app->bind(
            A04ReservationRepositoryInterface::class,
            A04EloquentReservationRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
