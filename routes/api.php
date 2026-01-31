<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\CouponController as PublicCouponController;
use App\Http\Controllers\Api\Admin\ShippingChargeController;
use App\Http\Controllers\Api\Admin\PaymentGatewayController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ====================================================
// 1. PUBLIC ROUTES (No Login Required)
// ====================================================

// --- Email & Password Authentication ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Social Authentication (Google & Facebook) ---
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// --- Password Reset (Forgot Password) ---
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);








// ====================================================
// 2. PROTECTED ROUTES (Login Required - Sanctum)
// ====================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // --- Common Routes (For All Logged-in Users) ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });







    // --- CUSTOMER PANEL (Only for Customers) ---
    Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
        // Checkout Route
        Route::post('/checkout', [OrderController::class, 'store']);
        // Address Routes (একটু আগে যা করলাম)
        Route::apiResource('addresses', UserAddressController::class);

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist/{product_id}', [WishlistController::class, 'destroy']);

        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::put('/cart/item/{id}', [CartController::class, 'update']); // Update Quantity
        Route::delete('/cart/item/{id}', [CartController::class, 'destroy']); // Remove Item
        Route::post('/apply-coupon', [PublicCouponController::class, 'apply']);


        });










    // --- ADMIN PANEL (Only for Super Admin & Employees) ---
    Route::middleware(['auth:sanctum', 'role:super-admin|employee'])->prefix('admin')->group(function () {

        // Category Routes
        Route::get('/categories/list', [CategoryController::class, 'list']); // Flat list for dropdowns
        Route::apiResource('categories', CategoryController::class);

        // Brand Routes
        Route::get('/brands/list', [BrandController::class, 'list']);
        Route::apiResource('brands', BrandController::class);
        Route::apiResource('products', ProductController::class);

        Route::get('/orders', [AdminOrderController::class, 'index']); // List
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']); // Details
        Route::put('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']); // Change Status
        Route::delete('/orders/{id}', [AdminOrderController::class, 'destroy']); // Delete
        Route::apiResource('coupons', AdminCouponController::class);
        Route::post('/coupons/{id}/toggle-status', [AdminCouponController::class, 'toggleStatus']);

        Route::apiResource('shipping-charges', ShippingChargeController::class);

    });






    // Super Admin Only Routes
    Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin')->group(function () {

        Route::get('/permissions', [EmployeeController::class, 'getGroupedPermissions']);

        // Employee CRUD Operations
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::put('/employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

        Route::get('/payment-gateways', [PaymentGatewayController::class, 'index']);
        Route::post('/payment-gateways/{id}', [PaymentGatewayController::class, 'update']);

    });
});
