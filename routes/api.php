<?php

use App\Architectures\A01_MonolithicEloquent\Phase_01\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('arch_01/v1')->group(function () {
    Route::post('reservation', [ReservationController::class, 'store']);
    Route::get('reservation/{id}', [ReservationController::class, 'show']);
});