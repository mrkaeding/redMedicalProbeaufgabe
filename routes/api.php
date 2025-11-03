<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdersController;


Route::get('v1/orders', [OrdersController::class, 'index']);
Route::get('v1/orders/{id}', [OrdersController::class, 'show']);
