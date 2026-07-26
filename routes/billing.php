<?php

use App\Http\Controllers\Billing\BillingPortalController;
use App\Http\Controllers\Billing\CreateCheckoutController;
use App\Http\Controllers\Billing\IndexBillingController;
use Illuminate\Support\Facades\Route;

Route::get('billing', IndexBillingController::class)->name('billing.index');
Route::post('billing/checkout/{plan}', CreateCheckoutController::class)->name('billing.checkout');
Route::get('billing/portal', BillingPortalController::class)->name('billing.portal');
