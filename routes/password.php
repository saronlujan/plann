<?php

use App\Http\Controllers\Password\ResetPasswordController;
use App\Http\Controllers\Password\SendResetPinController;
use App\Http\Controllers\Password\ShowForgotPasswordController;
use App\Http\Controllers\Password\ShowResetPasswordController;
use App\Http\Controllers\Password\VerifyPinController;
use Illuminate\Support\Facades\Route;

Route::get('/forgot-password', ShowForgotPasswordController::class)->name('password.request');
Route::post('/forgot-password', SendResetPinController::class)
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::get('/reset-password', ShowResetPasswordController::class)->name('password.reset');
Route::post('/reset-password/verify', VerifyPinController::class)
    ->middleware('throttle:6,1')
    ->name('password.verify');
Route::post('/reset-password', ResetPasswordController::class)
    ->middleware('throttle:6,1')
    ->name('password.update');
