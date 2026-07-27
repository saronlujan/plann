<?php

use App\Http\Controllers\Billing\BillingPortalController;
use App\Http\Controllers\Billing\CreateCheckoutController;
use App\Http\Controllers\Billing\IndexBillingController;
use App\Http\Controllers\Billing\RefreshSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('billing', IndexBillingController::class)->name('billing.index');
Route::post('billing/checkout/{plan}', CreateCheckoutController::class)->name('billing.checkout');
Route::get('billing/portal', BillingPortalController::class)->name('billing.portal');

// Throttled: each call hits the Stripe API.
Route::post('billing/refresh', RefreshSubscriptionController::class)
    ->middleware('throttle:6,1')
    ->name('billing.refresh');
