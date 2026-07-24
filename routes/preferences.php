<?php

use App\Http\Controllers\Preferences\IndexPreferencesController;
use App\Http\Controllers\Preferences\UpdatePreferencesLanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('preferences')->name('preferences.')->group(function (): void {
    Route::get('/', IndexPreferencesController::class)->name('index');
    Route::patch('/language', UpdatePreferencesLanguageController::class)->name('language.update');
});