<?php

use App\Http\Controllers\Currencies\IndexCurrenciesController;
use App\Http\Controllers\Currencies\UpdateCurrenciesController;
use Illuminate\Support\Facades\Route;

Route::get('currencies', IndexCurrenciesController::class)->name('currencies.index');
Route::patch('currencies', UpdateCurrenciesController::class)->name('currencies.update');
