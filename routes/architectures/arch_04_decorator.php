<?php

use Illuminate\Support\Facades\Route;

use App\Architectures\A04_DecoratorDomain\Phase_01\Controllers\ReservationController as A04Phase01Controller;
use App\Architectures\A04_DecoratorDomain\Phase_02\Controllers\ReservationController as A04Phase02Controller;
use App\Architectures\A04_DecoratorDomain\Phase_03\Controllers\ReservationController as A04Phase03Controller;

Route::prefix('arch_04/v1')->group(function () {
    Route::post('reservation', [A04Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A04Phase01Controller::class, 'show']);
});

Route::prefix('arch_04/v2')->group(function () {
    Route::post('reservation', [A04Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A04Phase02Controller::class, 'show']);
});

Route::prefix('arch_04/v3')->group(function () {
    Route::post('reservation', [A04Phase03Controller::class, 'store']);
    Route::get('reservation/{id}', [A04Phase03Controller::class, 'show']);
});
