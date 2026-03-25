<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// A02
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A02ReservationRepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A02EloquentReservationRepository;

// A03
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A03ReservationRepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A03EloquentReservationRepository;

// A04
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A04ReservationRepositoryInterface;
use App\Architectures\A04_DecoratorDomain\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A04EloquentReservationRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // A02 Binding
        $this->app->bind(
            A02ReservationRepositoryInterface::class,
            A02EloquentReservationRepository::class
        );

        // A03 Binding
        $this->app->bind(
            A03ReservationRepositoryInterface::class,
            A03EloquentReservationRepository::class
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
