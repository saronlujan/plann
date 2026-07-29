<?php

use App\Http\Controllers\Onboarding\IndexOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('onboarding', IndexOnboardingController::class)->name('onboarding');
