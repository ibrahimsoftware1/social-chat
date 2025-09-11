<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
    Route::get('/email/resend', [AuthController::class, 'resendVerificationEmail']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});


    // User Profile
    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::controller(UserController::class)->group(function () {

            Route::get('/','profile');
            Route::put('/update-profile','updateProfile');
            Route::post('/avatar','updateAvatar');
            Route::delete('/avatar','removeAvatar');
            Route::post('/change-password','changePassword');
            Route::get('/conversations','conversations');
            Route::delete('/delete-account','deleteAccount');
        });
    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::controller(AdminController::class)->group(function () {

            Route::get('/dashboard', 'dashboard');
            Route::get('/users', 'users');
            Route::get('/users/{id}', 'userDetails');
            Route::post('/users/{id}/ban', 'ban');
            Route::post('/users/{id}/unban', 'unban');
            Route::post('/users/{id}/assign-role', 'assignRole');
            Route::delete('/users/{id}', 'deleteUser');

        });
});

    // Conversations routes
    Route::middleware(['auth:sanctum', 'verified'])->prefix('conversations')->group(function () {
       Route::controller(ConversationController::class)->group(function () {

           Route::get('/', 'index');
           Route::post('/', 'store');
           Route::get('/{conversation}', 'show');
           Route::put('/{conversation}', 'update');
           Route::delete('/{conversation}', 'destroy');
           Route::post('/{conversation}/participants', 'addParticipants');
           Route::delete('/{conversation}/participants/{user}', 'removeParticipant');
           Route::post('/{conversation}/read', 'markAsRead');
           Route::get('/{conversation}/messages', 'messages');
       });
});




