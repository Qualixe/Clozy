<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shop\Http\Controllers\TrackOrderController;

/**
 * Guest order tracking — lookup by order number + email, no account required.
 */
Route::controller(TrackOrderController::class)->prefix('track-order')->group(function () {
    Route::get('', 'index')->name('shop.track_order.index');

    Route::post('', 'track')
        ->middleware('throttle:track-order-lookup')
        ->name('shop.track_order.track');
});
