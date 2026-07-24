<?php

use App\Http\Controllers\Settings\IndexSettingsController;
use App\Http\Controllers\Settings\UpdateSettingsCurrenciesController;
use App\Http\Controllers\Settings\UpdateSettingsLanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->name('settings.')->group(function (): void {
    Route::get('/', IndexSettingsController::class)->name('index');
    Route::patch('/currencies', UpdateSettingsCurrenciesController::class)->name('currencies.update');
    Route::patch('/language', UpdateSettingsLanguageController::class)->name('language.update');
});
