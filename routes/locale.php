<?php

use App\Http\Controllers\Locale\UpdateLocaleController;
use Illuminate\Support\Facades\Route;

Route::post('/locale', UpdateLocaleController::class)->name('locale.update');
