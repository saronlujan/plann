<?php

use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Login\LogoutController;
use App\Http\Controllers\Login\SessionController;
use App\Http\Controllers\Register\RegisterController;
use App\Http\Controllers\Register\StoreUserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', LoginController::class)->name('login');
Route::post('/login', SessionController::class)->name('login.store');
Route::get('/register', RegisterController::class)->name('register');
Route::post('/register', StoreUserController::class)->name('register.store');
Route::post('/logout', LogoutController::class)->name('logout')->withoutMiddleware('guest');
