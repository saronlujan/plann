<?php

use App\Http\Controllers\Profile\ReadProfileController;
use App\Http\Controllers\Profile\UpdatePasswordController;
use App\Http\Controllers\Profile\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::get('profile', ReadProfileController::class)->name('profile.edit');
Route::patch('profile', UpdateProfileController::class)->name('profile.update');
Route::put('profile/password', UpdatePasswordController::class)->name('profile.password');
