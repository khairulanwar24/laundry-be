<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DiscountController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OutletController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PerfumeController;
use App\Http\Controllers\Api\V1\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

// API v1 routes with prefix and name
Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes (no authentication required)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');
    });

    // OUTLETS
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });

        Route::get('/outlets', [OutletController::class, 'index'])->name('outlets.index');
        Route::post('/outlets', [OutletController::class, 'store'])->name('outlets.store'); // create outlet by owner

        Route::middleware('outlet.member')->group(function () {
            Route::get('/outlets/{outlet}', [OutletController::class, 'show'])->name('outlets.show');
            Route::put('/outlets/{outlet}', [OutletController::class, 'update'])->name('outlets.update');
            Route::post('/outlets/{outlet}/invite', [OutletController::class, 'invite'])->name('outlets.invite');
            Route::put('/outlets/{outlet}/members/{member}', [OutletController::class, 'updateMember'])->name('outlets.members.update');
            Route::delete('/outlets/{outlet}/members/{member}', [OutletController::class, 'removeMember'])->name('outlets.members.remove');

            // PAYMENT METHODS
            Route::get('/outlets/{outlet}/payment-methods', [PaymentMethodController::class, 'index'])->name('outlets.payment-methods.index');
            Route::post('/outlets/{outlet}/payment-methods', [PaymentMethodController::class, 'store'])->name('outlets.payment-methods.store');
            Route::put('/outlets/{outlet}/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('outlets.payment-methods.update');
            Route::delete('/outlets/{outlet}/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('outlets.payment-methods.destroy');

            // SERVICES & VARIANTS
            Route::get('/outlets/{outlet}/services', [ServiceCatalogController::class, 'index'])->name('outlets.services.index');
            Route::post('/outlets/{outlet}/services', [ServiceCatalogController::class, 'store'])->name('outlets.services.store');
            Route::put('/outlets/{outlet}/services/{service}', [ServiceCatalogController::class, 'update'])->name('outlets.services.update');
            Route::post('/outlets/{outlet}/services/{service}/variants', [ServiceCatalogController::class, 'storeVariant'])->name('outlets.services.variants.store');
            Route::put('/outlets/{outlet}/service-variants/{variant}', [ServiceCatalogController::class, 'updateVariant'])->name('outlets.service-variants.update');

            // PERFUMES
            Route::get('/outlets/{outlet}/perfumes', [PerfumeController::class, 'index'])->name('outlets.perfumes.index');
            Route::post('/outlets/{outlet}/perfumes', [PerfumeController::class, 'store'])->name('outlets.perfumes.store');
            Route::put('/outlets/{outlet}/perfumes/{perfume}', [PerfumeController::class, 'update'])->name('outlets.perfumes.update');

            // DISCOUNTS
            Route::get('/outlets/{outlet}/discounts', [DiscountController::class, 'index'])->name('outlets.discounts.index');
            Route::post('/outlets/{outlet}/discounts', [DiscountController::class, 'store'])->name('outlets.discounts.store');
            Route::put('/outlets/{outlet}/discounts/{discount}', [DiscountController::class, 'update'])->name('outlets.discounts.update');

            // ORDERS
            Route::get('/outlets/{outlet}/orders', [OrderController::class, 'index'])->name('outlets.orders.index');
            Route::post('/outlets/{outlet}/orders', [OrderController::class, 'store'])->name('outlets.orders.store');
            Route::get('/outlets/{outlet}/orders/{order}', [OrderController::class, 'show'])->name('outlets.orders.show');
            Route::post('/outlets/{outlet}/orders/{order}/status', [OrderController::class, 'status'])->name('outlets.orders.status');
            Route::post('/outlets/{outlet}/orders/{order}/pay', [OrderController::class, 'pay'])->name('outlets.orders.pay');
            Route::post('/outlets/{outlet}/orders/{order}/pickup', [OrderController::class, 'pickup'])->name('outlets.orders.pickup');

            // DASHBOARD
            Route::get('/outlets/{outlet}/dashboard/summary', [DashboardController::class, 'summary'])->name('outlets.dashboard.summary');

            // Test route for middleware
            Route::get('/outlets/{outlet}/test', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Access granted to outlet',
                    'data' => null,
                    'errors' => null,
                    'meta' => null,
                ]);
            })->name('outlets.test');
        });
    });
});

// API 404 fallback for JSON response
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint tidak ditemukan',
        'data' => null,
        'errors' => null,
        'meta' => null,
    ], 404);
});
