<?php

use Illuminate\Support\Facades\Route;

use App\Architectures\A04_DecoratorDomain\Phase_01\Controllers\ReservationController as A04Phase01Controller;

Route::prefix('arch_04/v1')->group(function () {
    Route::post('reservation', [A04Phase01Controller::class, 'store']);
    Route::get('reservation/{id}', [A04Phase01Controller::class, 'show']);
});
