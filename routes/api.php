<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMovementController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/product-movements', [ProductMovementController::class, 'index']);

Route::prefix('warehouses')->group(function () {
    Route::get('/', [WarehouseController::class, 'index']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/stock', [ProductController::class, 'stock']);
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::put('/{order}', [OrderController::class, 'update']);

    Route::post('/{order}/complete', [OrderController::class, 'complete']);
    Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/{order}/resume', [OrderController::class, 'resume']);
});
