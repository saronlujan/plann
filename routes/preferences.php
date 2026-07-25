<?php

use App\Http\Controllers\Preferences\ReadPreferencesController;
use Illuminate\Support\Facades\Route;

Route::get('preferences/', ReadPreferencesController::class)->name('preferences');
