<?php

use Illuminate\Support\Facades\Route;
use Webkul\Shop\Http\Controllers\CartController;
use Webkul\Shop\Http\Controllers\CheckoutEmailVerificationController;
use Webkul\Shop\Http\Controllers\OnepageController;

/**
 * Cart routes.
 */
Route::controller(CartController::class)->prefix('checkout/cart')->group(function () {
    Route::get('', 'index')->name('shop.checkout.cart.index');
});

Route::controller(OnepageController::class)->prefix('checkout/onepage')->group(function () {
    Route::get('', 'index')->name('shop.checkout.onepage.index');

    Route::get('success', 'success')->name('shop.checkout.onepage.success');
});

/**
 * Guest checkout email verification — gate placed before the onepage
 * checkout to reduce fake/bot orders by confirming the guest controls the
 * email address they're about to order under.
 */
Route::controller(CheckoutEmailVerificationController::class)->prefix('checkout/verify-email')->group(function () {
    Route::get('', 'index')->name('shop.checkout.verify_email.index');

    Route::post('send', 'send')
        ->middleware('throttle:checkout-otp-send')
        ->name('shop.checkout.verify_email.send');

    Route::post('verify', 'verify')
        ->middleware('throttle:checkout-otp-verify')
        ->name('shop.checkout.verify_email.verify');
});
