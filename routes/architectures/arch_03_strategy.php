<?php

use Illuminate\Support\Facades\Route;

use App\Architectures\A03_StrategyPolymorphism\Phase_01\Controllers\ReservationController as A03Phase01Controller;
use App\Architectures\A03_StrategyPolymorphism\Phase_02\Controllers\ReservationController as A03Phase02Controller;

Route::prefix('arch_03/v1')->group(function () {
    Route::post('reservation', [A03Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A03Phase01Controller::class, 'show']);
});

Route::prefix('arch_03/v2')->group(function () {
    Route::post('reservation', [A03Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A03Phase02Controller::class, 'show']);
});
