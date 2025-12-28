<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\DailyMealPlanController;
use App\Http\Controllers\MealPlanItemController;

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

    // Categories
    Route::apiResource('categories', CategoryController::class);
    
    // Meals
    Route::apiResource('meals', MealController::class);
    
    // Daily Meal Plans
    Route::get('meal-plans/today', [DailyMealPlanController::class, 'today']);
    Route::apiResource('meal-plans', DailyMealPlanController::class);
    
    // Meal Plan Items
    Route::post('meal-plan-items', [MealPlanItemController::class, 'store']);
    Route::put('meal-plan-items/{mealPlanItem}', [MealPlanItemController::class, 'update']);
    Route::delete('meal-plan-items/{mealPlanItem}', [MealPlanItemController::class, 'destroy']);

    // Admin only routes
    Route::middleware('is.admin')->prefix('admin')->group(function () {
        Route::get('/users', [AuthenticationController::class, 'userList']);
        Route::put('/users/{userId}/role', [AuthenticationController::class, 'updateUserRole']);
        Route::delete('/users/{userId}', [AuthenticationController::class, 'deleteUser']);
    });
});