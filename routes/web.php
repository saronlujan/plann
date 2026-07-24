<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Login\LogoutController;
use App\Http\Controllers\Login\SessionController;
use App\Http\Controllers\Register\RegisterController;
use App\Http\Controllers\Register\StoreUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', LoginController::class)->name('login');
    Route::post('/login', SessionController::class)->name('login.store');
    Route::get('/register', RegisterController::class)->name('register');
    Route::post('/register', StoreUserController::class)->name('register.store');
});

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function (): void {

    Route::get('/components', fn () => Inertia::render('Components/Index'))->name('components');

    Route::post('/logout', LogoutController::class)->name('logout');
});
