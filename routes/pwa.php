<?php

use App\Http\Controllers\Pwa\ManifestController;
use App\Http\Controllers\Pwa\ServiceWorkerController;
use Illuminate\Support\Facades\Route;

/*
 * Public by design: the worker and the offline fallback must be reachable with no
 * session, including on the very first visit and while signed out.
 */
Route::get('/manifest.webmanifest', ManifestController::class)->name('pwa.manifest');

Route::get('/sw.js', ServiceWorkerController::class)->name('pwa.service-worker');

Route::view('/offline', 'pwa.offline')->name('pwa.offline');
