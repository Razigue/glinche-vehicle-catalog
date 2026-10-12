<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/brands', [BrandController::class, 'index']);
Route::get('/vehicles', [VehicleController::class, 'index']);
