<?php

use Illuminate\Support\Facades\Route;

// A01 - Monolithic Phase 01
use App\Architectures\A01_MonolithicEloquent\Phase_01\Controllers\ReservationController as A01Phase01Controller;

// A01 - Monolithic Phase 02
use App\Architectures\A01_MonolithicEloquent\Phase_02\Controllers\ReservationController as A01Phase02Controller;

// A02 - Repository Phase 01
use App\Architectures\A02_RepositoryPattern\Phase_01\Controllers\ReservationController as A02Phase01Controller;

// A02 - Repository Phase 02
use App\Architectures\A02_RepositoryPattern\Phase_02\Controllers\ReservationController as A02Phase02Controller;

// A03 - Strategy + Polymorphism Phase 01
use App\Architectures\A03_StrategyPolymorphism\Phase_01\Controllers\ReservationController as A03Phase01Controller;

// A03 - Strategy + Polymorphism Phase 02
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Controllers\ReservationController as A03Phase02Controller;

// A04 - Decorator Domain
use App\Architectures\A04_DecoratorDomain\Phase_01\Controllers\ReservationController as A04ReservationController;


Route::prefix('arch_01/v1')->group(function () {
    Route::post('reservation', [A01Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A01Phase01Controller::class, 'show']);
});

Route::prefix('arch_01/v2')->group(function () {
    Route::post('reservation', [A01Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A01Phase02Controller::class, 'show']);
});


Route::prefix('arch_02/v1')->group(function () {
    Route::post('reservation', [A02Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A02Phase01Controller::class, 'show']);
});

Route::prefix('arch_02/v2')->group(function () {
    Route::post('reservation', [A02Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A02Phase02Controller::class, 'show']);
});

Route::prefix('arch_03/v1')->group(function () {
    Route::post('reservation', [A03Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A03Phase01Controller::class, 'show']);
});

Route::prefix('arch_03/v2')->group(function () {
    Route::post('reservation', [A03Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A03Phase02Controller::class, 'show']);
});

Route::prefix('arch_04/v1')->group(function () {
    Route::post('reservation', [A04ReservationController::class, 'store']);
    Route::get('reservation/{id}', [A04ReservationController::class, 'show']);
});
