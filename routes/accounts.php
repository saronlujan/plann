<?php

use App\Http\Controllers\Accounts\ReadAccountsController;
use Illuminate\Support\Facades\Route;

Route::get('accounts/', ReadAccountsController::class)->name('accounts');
