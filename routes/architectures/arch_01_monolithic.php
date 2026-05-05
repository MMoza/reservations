<?php

use Illuminate\Support\Facades\Route;

use App\Architectures\A01_MonolithicEloquent\Phase_01\Controllers\ReservationController as A01Phase01Controller;
use App\Architectures\A01_MonolithicEloquent\Phase_02\Controllers\ReservationController as A01Phase02Controller;
use App\Architectures\A01_MonolithicEloquent\Phase_03\Controllers\ReservationController as A01Phase03Controller;

Route::prefix('arch_01/v1')->group(function () {
    Route::post('reservation', [A01Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A01Phase01Controller::class, 'show']);
});

Route::prefix('arch_01/v2')->group(function () {
    Route::post('reservation', [A01Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A01Phase02Controller::class, 'show']);
});

Route::prefix('arch_01/v3')->group(function () {
    Route::post('reservation', [A01Phase03Controller::class, 'store']);
    Route::get('reservation/{id}', [A01Phase03Controller::class, 'show']);
});
