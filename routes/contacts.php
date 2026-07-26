<?php

use App\Http\Controllers\Contacts\DeleteContactController;
use App\Http\Controllers\Contacts\ReadContactsController;
use App\Http\Controllers\Contacts\StoreContactController;
use App\Http\Controllers\Contacts\UpdateContactController;
use Illuminate\Support\Facades\Route;

Route::get('/contacts', ReadContactsController::class)->name('contacts');
Route::post('/contacts', StoreContactController::class)->name('contacts.store');
Route::patch('/contacts/{contact}', UpdateContactController::class)->name('contacts.update');
Route::delete('/contacts/{contact}', DeleteContactController::class)->name('contacts.destroy');
