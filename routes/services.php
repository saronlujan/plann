<?php

use App\Http\Controllers\Services\DeleteServiceController;
use App\Http\Controllers\Services\IndexServicesController;
use App\Http\Controllers\Services\StoreServiceController;
use App\Http\Controllers\Services\UpdateServiceController;
use Illuminate\Support\Facades\Route;

Route::get('services', IndexServicesController::class)->name('services.index');
Route::post('services', StoreServiceController::class)->name('services.store');
Route::patch('services/{service}', UpdateServiceController::class)->name('services.update');
Route::delete('services/{service}', DeleteServiceController::class)->name('services.destroy');
