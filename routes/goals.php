<?php

use App\Http\Controllers\Goal\ContributeGoalController;
use App\Http\Controllers\Goal\DeleteGoalController;
use App\Http\Controllers\Goal\IndexGoalsController;
use App\Http\Controllers\Goal\StoreGoalController;
use App\Http\Controllers\Goal\UpdateGoalController;
use Illuminate\Support\Facades\Route;

Route::get('goals', IndexGoalsController::class)->name('goals.index');
Route::post('goals', StoreGoalController::class)->name('goals.store');
Route::patch('goals/{goal}', UpdateGoalController::class)->name('goals.update');
Route::post('goals/{goal}/contribute', ContributeGoalController::class)->name('goals.contribute');
Route::delete('goals/{goal}', DeleteGoalController::class)->name('goals.destroy');
