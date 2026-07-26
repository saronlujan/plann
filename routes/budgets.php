<?php

use App\Http\Controllers\Budget\DeleteBudgetController;
use App\Http\Controllers\Budget\IndexBudgetsController;
use App\Http\Controllers\Budget\StoreBudgetController;
use App\Http\Controllers\Budget\UpdateBudgetController;
use Illuminate\Support\Facades\Route;

Route::get('budgets', IndexBudgetsController::class)->name('budgets.index');
Route::post('budgets', StoreBudgetController::class)->name('budgets.store');
Route::patch('budgets/{budget}', UpdateBudgetController::class)->name('budgets.update');
Route::delete('budgets/{budget}', DeleteBudgetController::class)->name('budgets.destroy');
