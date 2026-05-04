<?php

use Illuminate\Support\Facades\Route;

use App\Architectures\A02_RepositoryPattern\Phase_01\Controllers\ReservationController as A02Phase01Controller;
use App\Architectures\A02_RepositoryPattern\Phase_02\Controllers\ReservationController as A02Phase02Controller;

Route::prefix('arch_02/v1')->group(function () {
    Route::post('reservation', [A02Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A02Phase01Controller::class, 'show']);
});

Route::prefix('arch_02/v2')->group(function () {
    Route::post('reservation', [A02Phase02Controller::class, 'store']);
    Route::get('reservation/{id}', [A02Phase02Controller::class, 'show']);
});
