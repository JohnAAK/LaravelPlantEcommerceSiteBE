<?php
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public catalog routes (Products, Categories, Stores) go here...
});
Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
});

// Protected Routes (Requires valid Sanctum Token)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Auth User Actions
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Customer Routes
    Route::middleware('role:customer,vendor,admin')->group(function () {
        // Cart, Checkout, Order Tracking, Profile...
    });

    // Vendor Routes
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        // Product Management, Store Profile Update, Vendor Sales Dashboard...
    });

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Store Approvals, Category CRUD, Platform Settings...
    });

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        Route::get('/products', [ProductController::class, 'vendorProducts']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });
    });
});