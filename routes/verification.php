<?php

use App\Http\Controllers\Verification\ResendVerificationPinController;
use App\Http\Controllers\Verification\ShowVerifyEmailController;
use App\Http\Controllers\Verification\VerifyEmailPinController;
use Illuminate\Support\Facades\Route;

/*
 * Reachable while signed in but unverified — this is the only place an
 * unverified account can go, so it must not carry the "verified" middleware.
 */
Route::get('/verify-email', ShowVerifyEmailController::class)->name('verification.notice');

Route::post('/verify-email', VerifyEmailPinController::class)
    ->middleware('throttle:6,1')
    ->name('verification.verify');

Route::post('/verify-email/resend', ResendVerificationPinController::class)
    ->middleware('throttle:3,1')
    ->name('verification.resend');
