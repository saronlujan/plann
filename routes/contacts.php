<?php

use App\Http\Controllers\Contacts\ReadContactsController;
use Illuminate\Support\Facades\Route;

Route::get('contacts/', ReadContactsController::class)->name('contacts');
