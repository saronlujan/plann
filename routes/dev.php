<?php

use App\Http\Controllers\Dev\ExpireTrialController;
use Illuminate\Support\Facades\Route;

/*
 * TEMPORARY dev-only routes for exercising the billing/paywall flow.
 * Registered exclusively in the local environment (see RouteServiceProvider).
 * Delete this file after Stripe testing is finished.
 */
Route::post('dev/expire-trial', ExpireTrialController::class)->name('dev.expire-trial');
