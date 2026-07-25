<?php

use App\Http\Controllers\Settings\ReadSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', ReadSettingsController::class)->name('settings');
