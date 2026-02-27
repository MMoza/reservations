<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// A02
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A02ReservationRepositoryInterface;
use App\Architectures\A02_RepositoryPattern\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A02EloquentReservationRepository;

// A03
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Contracts\ReservationRepositoryInterface as A03ReservationRepositoryInterface;
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Repositories\Eloquent\EloquentReservationRepository as A03EloquentReservationRepository;

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
    }

    public function boot(): void
    {
        //
    }
}