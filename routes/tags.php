<?php

use App\Http\Controllers\Tags\DeleteTagController;
use App\Http\Controllers\Tags\IndexTagsController;
use App\Http\Controllers\Tags\StoreTagController;
use App\Http\Controllers\Tags\UpdateTagController;
use Illuminate\Support\Facades\Route;

Route::get('tags', IndexTagsController::class)->name('tags.index');
Route::post('tags', StoreTagController::class)->name('tags.store');
Route::patch('tags/{tag}', UpdateTagController::class)->name('tags.update');
Route::delete('tags/{tag}', DeleteTagController::class)->name('tags.destroy');
