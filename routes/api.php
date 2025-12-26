<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthenticationController::class, 'register']);
    Route::post('/login', [AuthenticationController::class, 'login']);
    Route::post('/verify-otp', [AuthenticationController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthenticationController::class, 'resendOtp']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/me', [AuthenticationController::class, 'me']);
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        Route::post('/logout-all', [AuthenticationController::class, 'logoutAllDevices']);
    });

    // Admin only routes
    Route::middleware('is.admin')->prefix('admin')->group(function () {
        Route::get('/users', [AuthenticationController::class, 'userList']);
        Route::put('/users/{userId}/role', [AuthenticationController::class, 'updateUserRole']);
        Route::delete('/users/{userId}', [AuthenticationController::class, 'deleteUser']);
    });
});