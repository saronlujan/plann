<?php

use App\Http\Controllers\Reports\IndexReportsController;
use Illuminate\Support\Facades\Route;

Route::get('reports', IndexReportsController::class)->name('reports.index');
