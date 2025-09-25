<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Chatting\ConversationController;
use App\Http\Controllers\Api\Chatting\MessageController;
use App\Http\Controllers\Api\Social\FollowController;
use App\Http\Controllers\Api\Social\PostController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Authentication routes
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


    // Messages routes
Route::middleware(['auth:sanctum', 'verified'])->prefix('messages')->group(function () {
    Route::controller(MessageController::class)->group(function () {
        Route::post('/conversations/{conversation}', 'store');
        Route::put('/{message}', 'update');
        Route::delete('/{message}', 'destroy');
        Route::post('/{message}/read', 'markAsRead');
        Route::post('/conversations/{conversation}/typing', 'typing');
        Route::post('/conversations/{conversation}/stop-typing', 'stopTyping');
    });
});

// Post routes
Route::middleware(['auth:sanctum', 'verified'])->prefix('posts')->group(function () {
    Route::controller(PostController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{post}', 'show');
        Route::put('/{post}', 'update');
        Route::delete('/{post}', 'destroy');
        Route::post('/{post}/pin', 'togglePin');
        Route::get('/user/{user}', 'userPosts');
        Route::get('/feed', 'feed');
        Route::get('/explore', 'index');
    });
});

// Follow routes
Route::middleware(['auth:sanctum', 'verified'])->prefix('users')->group(function () {
    Route::controller(FollowController::class)->group(function () {
        Route::post('/{user}/follow', 'follow');
        Route::delete('/{user}/unfollow', 'unfollow');
        Route::get('/{user}/followers', 'followers');
        Route::get('/{user}/following', 'following');});

});
