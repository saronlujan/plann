<?php

use App\Http\Controllers\Profile\ReadProfileController;
use App\Http\Controllers\Profile\ShowAvatarController;
use App\Http\Controllers\Profile\UpdateAvatarController;
use App\Http\Controllers\Profile\UpdatePasswordController;
use App\Http\Controllers\Profile\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::get('profile', ReadProfileController::class)->name('profile.edit');
Route::patch('profile', UpdateProfileController::class)->name('profile.update');
Route::put('profile/password', UpdatePasswordController::class)->name('profile.password');
Route::post('profile/avatar', UpdateAvatarController::class)->name('profile.avatar.update');
Route::get('profile/avatar', ShowAvatarController::class)->name('profile.avatar');
