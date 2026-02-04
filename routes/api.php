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
use App\Http\Controllers\Api\Admin\ManualPaymentMethodController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\SliderController;
use App\Http\Controllers\Api\Admin\PageController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ShopController;

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



    Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);

    Route::get('/settings', [SettingController::class, 'index']); // Get Logo, Phone, etc.
    Route::get('/sliders', [SliderController::class, 'activeSliders']); // Get Homepage Banners
    Route::get('/pages/{slug}', [PageController::class, 'getBySlug']); // Get Privacy Policy etc.
    Route::get('/shop/products', [ShopController::class, 'index']);       // Search & Filter
    Route::get('/shop/products/{slug}', [ShopController::class, 'show']); // Single Details
    Route::get('/shop/categories', [ShopController::class, 'categories']);// Menu
    Route::get('/shop/brands', [ShopController::class, 'brands']);


// ====================================================
// 2. PROTECTED ROUTES (Login Required - Sanctum)
// ====================================================

Route::middleware(['auth:sanctum'])->group(function () {

    // --- Common Routes (For All Logged-in Users) ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/tickets', [TicketController::class, 'index']);           // List
    Route::post('/tickets', [TicketController::class, 'store']);          // Create
    Route::get('/tickets/{id}', [TicketController::class, 'show']);       // View Chat
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']); // Reply
    Route::post('/tickets/{id}/close', [TicketController::class, 'close']); // Close

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'readSingle']);






    // --- CUSTOMER PANEL (Only for Customers) ---
    Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
        // Checkout Route
        Route::post('/checkout', [OrderController::class, 'store']);
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
        Route::get('/manual-payments', [ManualPaymentMethodController::class, 'activeMethods']);
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
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
        Route::apiResource('manual-payments', ManualPaymentMethodController::class);
        // Dashboard Analytics
        Route::get('/dashboard-stats', [DashboardController::class, 'index']);

        Route::get('/reviews', [AdminReviewController::class, 'index']);
        Route::post('/reviews/{id}/status', [AdminReviewController::class, 'updateStatus']);
        Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy']);

        Route::post('/settings', [SettingController::class, 'update']);

        // Sliders
        Route::apiResource('/sliders', SliderController::class);

        // Pages (CMS)
        Route::apiResource('/pages', PageController::class);

    });



    Route::middleware(['auth:sanctum', 'role:super-admin|employee'])->prefix('admin/reports')->group(function () {

        Route::get('/sales', [ReportController::class, 'sales']);
        Route::get('/orders', [ReportController::class, 'orders']);
        Route::get('/payments', [ReportController::class, 'payments']);
        Route::get('/customers', [ReportController::class, 'customers']);
        Route::get('/products', [ReportController::class, 'products']);
        Route::get('/export', [ReportController::class, 'export']);

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
