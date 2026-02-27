<?php

use Illuminate\Support\Facades\Route;

// A01 - Monolithic
use App\Architectures\A01_MonolithicEloquent\Phase_01\Controllers\ReservationController as A01ReservationController;

// A02 - Repository
use App\Architectures\A02_RepositoryPattern\Phase_01\Controllers\ReservationController as A02ReservationController;


Route::prefix('arch_01/v1')->group(function () {
    Route::post('reservation', [A01ReservationController::class, 'store']);
    Route::get('reservation/{id}', [A01ReservationController::class, 'show']);
});


Route::prefix('arch_02/v1')->group(function () {
    Route::post('reservation', [A02ReservationController::class, 'store']);
    Route::get('reservation/{id}', [A02ReservationController::class, 'show']);
});