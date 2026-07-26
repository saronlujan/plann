<?php

use App\Http\Controllers\Settings\Categories\DeleteCategoryController;
use App\Http\Controllers\Settings\Categories\IndexCategoriesController;
use App\Http\Controllers\Settings\Categories\StoreCategoryController;
use App\Http\Controllers\Settings\Categories\UpdateCategoryController;
use App\Http\Controllers\Settings\Currencies\IndexCurrenciesController;
use App\Http\Controllers\Settings\Currencies\UpdateCurrenciesController;
use App\Http\Controllers\Settings\Tags\DeleteTagController;
use App\Http\Controllers\Settings\Tags\IndexTagsController;
use App\Http\Controllers\Settings\Tags\StoreTagController;
use App\Http\Controllers\Settings\Tags\UpdateTagController;
use Illuminate\Support\Facades\Route;

Route::redirect('/settings', '/settings/categories')->name('settings');

Route::get('/settings/categories', IndexCategoriesController::class)->name('settings.categories.index');
Route::post('/settings/categories', StoreCategoryController::class)->name('settings.categories.store');
Route::patch('/settings/categories/{category}', UpdateCategoryController::class)->name('settings.categories.update');
Route::delete('/settings/categories/{category}', DeleteCategoryController::class)->name('settings.categories.destroy');

Route::get('/settings/tags', IndexTagsController::class)->name('settings.tags.index');
Route::post('/settings/tags', StoreTagController::class)->name('settings.tags.store');
Route::patch('/settings/tags/{tag}', UpdateTagController::class)->name('settings.tags.update');
Route::delete('/settings/tags/{tag}', DeleteTagController::class)->name('settings.tags.destroy');

Route::get('/settings/currencies', IndexCurrenciesController::class)->name('settings.currencies.index');
Route::patch('/settings/currencies', UpdateCurrenciesController::class)->name('settings.currencies.update');
