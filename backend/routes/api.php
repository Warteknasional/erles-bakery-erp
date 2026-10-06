<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Erles Bakery ERP
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', HealthController::class);

// ─────────────────────────────────────────────────────────────
// Public Routes
// ─────────────────────────────────────────────────────────────

// Auth
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Public Product Catalog
Route::middleware('throttle:public-api')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{idOrSlug}', [ProductController::class, 'show']);
    Route::get('/orders/track/{idOrCode}', [OrderController::class, 'show']);
});

// Public Order Placement
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:public-order');

// ─────────────────────────────────────────────────────────────
// Protected Admin Routes (Sanctum)
// ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Auth profile & logout
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Product Management (CUD)
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::patch('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Admin Order Management
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    // Admin Finance Management
    Route::get('/finance/summary', [FinanceController::class, 'summary']);
    Route::get('/finance', [FinanceController::class, 'index']);
    Route::get('/finance/{finance}', [FinanceController::class, 'show']);
    Route::post('/finance', [FinanceController::class, 'store']);
    Route::put('/finance/{finance}', [FinanceController::class, 'update']);
    Route::patch('/finance/{finance}', [FinanceController::class, 'update']);
    Route::delete('/finance/{finance}', [FinanceController::class, 'destroy']);
});
