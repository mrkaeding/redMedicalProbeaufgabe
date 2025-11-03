<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdersController;


Route::get('orders', [OrdersController::class, 'index']);
Route::get('orders/{id}', [OrdersController::class, 'show']);
Route::post('orders', [OrdersController::class, 'store']);
