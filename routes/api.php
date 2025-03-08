<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminControllers\UserController;

/*
 * Public Routes (No Authentication Required)
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-password-otp', [AuthController::class, 'verifyPasswordOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

/*
 * Protected Routes (Requires Authentication via Sanctum)
 */
Route::middleware('auth:sanctum')->group(function () {
    // Logout route (accessible to all authenticated users)
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin routes (permission-based authorization handled in controller)
    Route::prefix('admin')->group(function () {
        Route::post('/users', [UserController::class, 'addUsers']);          // Add a new user
        Route::get('/users/{role}', [UserController::class, 'getUsersByRole']); // Get users by role
        Route::delete('/users/{id}', [UserController::class, 'deleteUser']);    // Delete a user by ID
    });
});
