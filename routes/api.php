<?php

use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Restaurant data — all scoped to the authenticated user's current workspace.
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Reads any active member needs (waiters/kitchen build orders from the menu).
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('menu-items', MenuItemController::class)
        ->parameters(['menu-items' => 'menuItem'])->only(['index', 'show']);
    Route::apiResource('tables', RestaurantTableController::class)
        ->parameters(['tables' => 'restaurantTable'])->only(['index', 'show']);

    // Orders are the day-to-day job of every member. Writes blocked on a
    // read-only (unpaid/over-limit) restaurant; reads always allowed.
    Route::apiResource('orders', OrderController::class)->middleware('workspace.writable');

    // The signed-in user's own account (any member manages their own profile).
    Route::patch('user', [ProfileController::class, 'update']);
    Route::put('user/password', [ProfileController::class, 'updatePassword']);

    // Account subscription (per user): plan, trial, limits. Not workspace-scoped,
    // and deliberately NOT behind workspace.writable so a user can always upgrade.
    Route::get('subscription', [SubscriptionController::class, 'show']);
    Route::put('subscription', [SubscriptionController::class, 'update']);

    // Current workspace profile (read) + the switcher list / create / switch.
    Route::get('workspace', [WorkspaceController::class, 'show']);
    Route::get('workspaces', [WorkspaceController::class, 'index']);
    Route::post('workspaces', [WorkspaceController::class, 'store']);
    Route::put('workspaces/{workspace}/current', [WorkspaceController::class, 'switch']);

    // Plan + real usage counters (any member can view billing).
    Route::get('billing', [BillingController::class, 'show']);

    // Billing sub-resources: any member can read, admins mutate (below).
    Route::apiResource('payment-methods', PaymentMethodController::class)
        ->parameters(['payment-methods' => 'paymentMethod'])->only(['index', 'show']);
    Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);

    // Admin-only management: menu/table/staff/billing writes + analytics.
    // Also gated by workspace.writable so a read-only restaurant can't be edited.
    Route::middleware(['workspace.admin', 'workspace.writable'])->group(function () {
        Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('menu-items', MenuItemController::class)
            ->parameters(['menu-items' => 'menuItem'])->only(['store', 'update', 'destroy']);
        Route::apiResource('tables', RestaurantTableController::class)
            ->parameters(['tables' => 'restaurantTable'])->only(['store', 'update', 'destroy']);
        Route::apiResource('staff', StaffController::class);

        // Billing writes (local CRUD — no Stripe).
        Route::apiResource('payment-methods', PaymentMethodController::class)
            ->parameters(['payment-methods' => 'paymentMethod'])->only(['store', 'update', 'destroy']);
        Route::apiResource('invoices', InvoiceController::class)->only(['store', 'update', 'destroy']);

        Route::match(['put', 'patch'], 'workspace', [WorkspaceController::class, 'update']);
        Route::get('dashboard', [DashboardController::class, 'index']);
    });
});

// Public, unauthenticated endpoints — rate-limited by IP.
Route::middleware('throttle:public')->group(function () {
    // Customer order tracking by unguessable token.
    Route::get('/track/{token}', [OrderTrackingController::class, 'show']);

    // Customer menu for a restaurant, by workspace slug (QR-code landing).
    Route::get('/menu/{slug}', [PublicMenuController::class, 'show']);

    // Cascading location lookups (Malaysia): country → states → cities → postcodes
    Route::get('/states/{iso2?}', [LocationController::class, 'states']);
    Route::get('/states/{state}/cities', [LocationController::class, 'cities']);
    Route::get('/cities/{city}/postcodes', [LocationController::class, 'postcodes']);
});
