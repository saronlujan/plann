<?php

use App\Http\Controllers\Accounts\DeleteAccountController;
use App\Http\Controllers\Accounts\PayInvoiceController;
use App\Http\Controllers\Accounts\ReadAccountsController;
use App\Http\Controllers\Accounts\ShowAccountController;
use App\Http\Controllers\Accounts\StoreAccountController;
use App\Http\Controllers\Accounts\UpdateAccountController;
use Illuminate\Support\Facades\Route;

Route::get('accounts', ReadAccountsController::class)->name('accounts');
Route::post('accounts', StoreAccountController::class)->name('accounts.store');
Route::get('accounts/{account}', ShowAccountController::class)->name('accounts.show');
Route::patch('accounts/{account}', UpdateAccountController::class)->name('accounts.update');
Route::delete('accounts/{account}', DeleteAccountController::class)->name('accounts.destroy');
Route::post('accounts/{account}/pay-invoice', PayInvoiceController::class)->name('accounts.pay-invoice');
