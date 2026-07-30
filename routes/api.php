<?php
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaystackWebhookController;
use App\Http\Controllers\Api\V1\Vendor\VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\VendorAnalyticsController;
use App\Http\Controllers\Api\V1\BuyerOrderController;
use App\Http\Controllers\Api\V1\VendorOnboardingController;
use App\Http\Controllers\Api\V1\Admin\AdminStoreController;
use App\Http\Controllers\Api\V1\ReviewController;
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

Route::post('/v1/paystack/webhook', [PaystackWebhookController::class, 'handleWebhook']);

Route::get('/v1/products/{product}/reviews', [ReviewController::class, 'index']);

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

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Cart Routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::patch('/cart/{cart}', [CartController::class, 'update']);
    Route::delete('/cart/{cart}', [CartController::class, 'destroy']);

    // Checkout Pipeline
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    });

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/payment/initialize', [PaymentController::class, 'initialize']);
    Route::post('/payment/verify', [PaymentController::class, 'verify'])->name('paystack.callback');
    });

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::middleware('role:vendor')->prefix('vendor')->group(function () {
        // Vendor Order Management
        Route::get('/orders', [VendorOrderController::class, 'index']);
        Route::get('/orders/{order}', [VendorOrderController::class, 'show']);
        Route::patch('/orders/{order}/status', [VendorOrderController::class, 'updateStatus']);

        // Vendor Analytics Dashboard
        Route::get('/analytics', [VendorAnalyticsController::class, 'index']);
    });
    });

   

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Buyer Order History & Tracking
    Route::get('/buyer/orders', [BuyerOrderController::class, 'index']);
    Route::get('/buyer/orders/{orderNumber}', [BuyerOrderController::class, 'show']);
    Route::get('/buyer/orders/{orderNumber}/track', [BuyerOrderController::class, 'track']);
    });

    Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Buyer Vendor Application
    Route::post('/vendor/apply', [VendorOnboardingController::class, 'apply']);
    Route::get('/vendor/application-status', [VendorOnboardingController::class, 'applicationStatus']);

    // Admin Store Review Pipeline
    Route::middleware('role:admin')->prefix('admin/stores')->group(function () {
        Route::get('/', [AdminStoreController::class, 'index']);
        Route::patch('/{store}/approve', [AdminStoreController::class, 'approve']);
        Route::patch('/{store}/reject', [AdminStoreController::class, 'reject']);
        Route::patch('/{store}/suspend', [AdminStoreController::class, 'suspend']);
    });
    });

    Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    });

});