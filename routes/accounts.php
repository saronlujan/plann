<?php

use App\Http\Controllers\Accounts\PayInvoiceController;
use App\Http\Controllers\Accounts\ReadAccountsController;
use App\Http\Controllers\Accounts\ShowAccountController;
use Illuminate\Support\Facades\Route;

Route::get('accounts', ReadAccountsController::class)->name('accounts');
Route::get('accounts/{account}', ShowAccountController::class)->name('accounts.show');
Route::post('accounts/{account}/pay-invoice', PayInvoiceController::class)->name('accounts.pay-invoice');
