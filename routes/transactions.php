<?php

use App\Http\Controllers\Transaction\DeleteTransactionController;
use App\Http\Controllers\Transaction\IndexTransactionController;
use App\Http\Controllers\Transaction\PayTransactionController;
use App\Http\Controllers\Transaction\ShowTransactionAttachmentController;
use App\Http\Controllers\Transaction\StoreTransactionController;
use App\Http\Controllers\Transaction\UpdateTransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/transactions', IndexTransactionController::class)->name('transactions.index');
Route::get('/transactions/{transaction}/attachment', ShowTransactionAttachmentController::class)->name('transactions.attachment');
Route::post('/transactions', StoreTransactionController::class)->name('transactions.store');
Route::patch('/transactions/{transaction}', UpdateTransactionController::class)->name('transactions.update');
Route::patch('/transactions/{transaction}/pay', PayTransactionController::class)->name('transactions.pay');
Route::delete('/transactions/{transaction}', DeleteTransactionController::class)->name('transactions.destroy');
