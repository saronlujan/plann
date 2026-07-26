<?php

use App\Http\Controllers\Categories\DeleteCategoryController;
use App\Http\Controllers\Categories\IndexCategoriesController;
use App\Http\Controllers\Categories\StoreCategoryController;
use App\Http\Controllers\Categories\UpdateCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('categories', IndexCategoriesController::class)->name('categories.index');
Route::post('categories', StoreCategoryController::class)->name('categories.store');
Route::patch('categories/{category}', UpdateCategoryController::class)->name('categories.update');
Route::delete('categories/{category}', DeleteCategoryController::class)->name('categories.destroy');
