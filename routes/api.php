<?php

use Illuminate\Support\Facades\Route;

Route::post('register', 'App\Http\Controllers\API\AuthenticationController@register')->name('register');
Route::post('login', 'App\Http\Controllers\API\AuthenticationController@login')->name('login');

// Email verification - clicking link from email (NO AUTH REQUIRED)
Route::get('/email/verify/{id}/{hash}', 'App\Http\Controllers\API\EmailVerificationController@verify')
    ->middleware(['signed'])
    ->name('verification.verify');

// Email verification routes (REQUIRE AUTH)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/email/verify', 'App\Http\Controllers\API\EmailVerificationController@notice')
        ->name('verification.notice');
    
    Route::post('/email/verification-notification', 'App\Http\Controllers\API\EmailVerificationController@resend')
        ->middleware('throttle:6,1')
        ->name('verification.send');
    
    // Other protected routes
    Route::get('get-user', 'App\Http\Controllers\API\AuthenticationController@userInfo');
    Route::post('logout', 'App\Http\Controllers\API\AuthenticationController@logOut');
});