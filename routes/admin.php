<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IndexTenantsController;
use App\Http\Controllers\Admin\ShowTenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('tenants', IndexTenantsController::class)->name('tenants.index');
    Route::get('tenants/{tenant}', ShowTenantController::class)->name('tenants.show');
});
