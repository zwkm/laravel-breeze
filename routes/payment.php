<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentLinkController;


# Recommended integrations: Stripe Payment Links
Route::prefix('payment-link')->name('payment.link.')->group(function () {
        Route::get('/plan', [PaymentLinkController::class, 'plan'])->name('plan');
        Route::post('/payment', [PaymentLinkController::class, 'payment'])->name('payment');
        Route::get('/success', [PaymentLinkController::class, 'success'])->name('success');
});
