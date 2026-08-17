<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\LeaseController;
use App\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PropertyUnitController;
use App\Http\Controllers\Api\V1\ReceiptController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
            ],
            'message' => 'Gestion Locative API is running',
        ]);
    });

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:5,1');
        Route::post('/google', [AuthController::class, 'google'])->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/complete-profile', [AuthController::class, 'completeProfile']);
        });
    });

    // Protected routes
    Route::middleware(['auth:sanctum', 'profile.complete'])->group(function () {

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/avatar', [UserController::class, 'uploadAvatar']);
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);

        // Properties
        Route::get('/properties/search', [PropertyController::class, 'search']);
        Route::get('/properties/available', [PropertyController::class, 'available']);
        Route::get('/properties', [PropertyController::class, 'index']);
        Route::post('/properties', [PropertyController::class, 'store']);
        Route::get('/properties/{property}', [PropertyController::class, 'show']);
        Route::put('/properties/{property}', [PropertyController::class, 'update']);
        Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);
        Route::post('/properties/{property}/images', [PropertyController::class, 'uploadImage']);
        Route::delete('/properties/{property}/images/{imageId}', [PropertyController::class, 'deleteImage']);

        // Units
        Route::get('/properties/{property}/units', [PropertyUnitController::class, 'index']);
        Route::post('/properties/{property}/units', [PropertyUnitController::class, 'store']);
        Route::get('/units/{unit}', [PropertyUnitController::class, 'show']);
        Route::put('/units/{unit}', [PropertyUnitController::class, 'update']);
        Route::delete('/units/{unit}', [PropertyUnitController::class, 'destroy']);

        // Leases
        Route::get('/leases', [LeaseController::class, 'index']);
        Route::post('/leases', [LeaseController::class, 'store']);
        Route::get('/leases/{lease}', [LeaseController::class, 'show']);
        Route::put('/leases/{lease}', [LeaseController::class, 'update']);
        Route::post('/leases/{lease}/terminate', [LeaseController::class, 'terminate']);
        Route::post('/leases/{lease}/renew', [LeaseController::class, 'renew']);
        Route::get('/leases/{lease}/download', [LeaseController::class, 'download']);

        // Payments
        Route::get('/payments/history', [PaymentController::class, 'history']);
        Route::get('/payments/stats', [PaymentController::class, 'stats']);
        Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->middleware('throttle:10,1');
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::put('/payments/{payment}', [PaymentController::class, 'update']);
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);

        // Receipts
        Route::get('/receipts', [ReceiptController::class, 'index']);
        Route::get('/receipts/{receipt}', [ReceiptController::class, 'show']);
        Route::get('/receipts/{receipt}/download', [ReceiptController::class, 'download']);

        // Maintenance Requests
        Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
        Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store']);
        Route::get('/maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show']);
        Route::put('/maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'update']);
        Route::delete('/maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'destroy']);
        Route::post('/maintenance-requests/{maintenanceRequest}/comments', [MaintenanceRequestController::class, 'storeComment']);
        Route::put('/maintenance-requests/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'updateStatus']);

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show']);
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

        // Messages
        Route::get('/conversations', [MessageController::class, 'conversations']);
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'messages']);
        Route::post('/messages', [MessageController::class, 'store']);
        Route::put('/messages/{message}/read', [MessageController::class, 'markRead']);
        Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

        // Dashboard
        Route::get('/dashboard/owner', [DashboardController::class, 'owner']);
        Route::get('/dashboard/tenant', [DashboardController::class, 'tenant']);
        Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);
        Route::get('/dashboard/occupancy', [DashboardController::class, 'occupancy']);

        // Subscription
        Route::get('/subscription', [SubscriptionController::class, 'show']);
        Route::post('/subscription/initiate', [SubscriptionController::class, 'initiate']);

        // Admin
        Route::get('/admin/owners', [SubscriptionController::class, 'adminOverview']);
        Route::get('/admin/owners/{owner}', [SubscriptionController::class, 'ownerDetail']);
        Route::put('/admin/subscriptions/{subscription}/validate', [SubscriptionController::class, 'validateSubscription']);
    });
});
