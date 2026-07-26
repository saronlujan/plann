<?php

use App\Http\Controllers\Preferences\ReadPreferencesController;
use App\Http\Controllers\Preferences\UpdatePreferencesController;
use Illuminate\Support\Facades\Route;

Route::get('preferences', ReadPreferencesController::class)->name('preferences');
Route::patch('preferences', UpdatePreferencesController::class)->name('preferences.update');
