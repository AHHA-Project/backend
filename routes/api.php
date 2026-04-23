<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\DailyMealPlanController;
use App\Http\Controllers\MealPlanItemController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\PopularMealController;
use App\Http\Controllers\FavoriteController;


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
    // Profile
    Route::post('/user/profile-image', [UserController::class, 'updateProfileImage']);

     // Mobile App Meal Planner
    Route::prefix('daily-meal-plans')->group(function () {
        Route::get('/', [DailyMealPlanController::class, 'index']);
        Route::post('/add-meal', [DailyMealPlanController::class, 'addMeal']);
    });

    // User Preferences & Recommendations
    Route::post('/user/preferences', [UserPreferenceController::class, 'store']);
    Route::get('/recommend-meals', [RecommendationController::class, 'index']);

    // Popular Meals
    Route::get('/popular-meals', [PopularMealController::class, 'index']);

    // Favorites
    Route::prefix('favorites')->group(function () {
    Route::get('/',              [FavoriteController::class, 'index']);
    Route::post('/',             [FavoriteController::class, 'store']);
    Route::delete('/{mealId}',   [FavoriteController::class, 'destroy']);
    Route::post('/toggle',       [FavoriteController::class, 'toggle']);
});

    // Categories
    Route::apiResource('categories', CategoryController::class);
    
    // Meals
    Route::apiResource('meals', MealController::class);
    
    // Daily Meal Plans
    Route::get('meal-plans/today', [DailyMealPlanController::class, 'today']);
    Route::apiResource('meal-plans', DailyMealPlanController::class);
    // routes/api.php

    
    // Meal Plan Items
    Route::post('meal-plan-items', [MealPlanItemController::class, 'store']);
    Route::put('meal-plan-items/{mealPlanItem}', [MealPlanItemController::class, 'update']);
    Route::delete('meal-plan-items/{mealPlanItem}', [MealPlanItemController::class, 'destroy']);

    // Admin only routes
    Route::middleware('is.admin')->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'userList']);
        Route::put('/users/{userId}/role', [UserController::class, 'updateUserRole']);
        Route::patch('/users/{userId}', [UserController::class, 'toggleUserStatus']);
    });
});