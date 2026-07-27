<?php

use App\Http\Controllers\Currencies\DeleteCurrencyController;
use App\Http\Controllers\Currencies\IndexCurrenciesController;
use App\Http\Controllers\Currencies\StoreCurrencyController;
use App\Http\Controllers\Currencies\UpdateCurrenciesController;
use App\Http\Controllers\Currencies\UpdateCurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('currencies', IndexCurrenciesController::class)->name('currencies.index');
// Collection level: which currencies the workspace keeps active.
Route::patch('currencies', UpdateCurrenciesController::class)->name('currencies.activations');

// Member level: the workspace's own currencies.
Route::post('currencies', StoreCurrencyController::class)->name('currencies.store');
Route::patch('currencies/{currency}', UpdateCurrencyController::class)->name('currencies.update');
Route::delete('currencies/{currency}', DeleteCurrencyController::class)->name('currencies.destroy');
