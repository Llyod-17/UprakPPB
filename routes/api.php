<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
 
// ============================================================
// DEFAULT LARAVEL - Cek user yang sedang login
// ============================================================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
 
// ============================================================
// D.2 KATEGORI PRODUK
// ============================================================
 
// Public (tanpa auth token)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
 
// Protected (butuh auth token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
});
 
// ============================================================
// D.3 PRODUK
// ============================================================
 
// Public (tanpa auth token)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
 
// Protected (butuh auth token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::patch('/products/{id}/toggle', [ProductController::class, 'toggle']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});
 
// ============================================================
// D.4 PESANAN (ORDERS)
// ============================================================
 
// Semua endpoint order butuh auth token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
});