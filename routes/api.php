<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Google Authentication Routes
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::post('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Public post routes
Route::get('/posts', [PostController::class, 'index']);
Route::get('/user_posts', [PostController::class, 'UserPosts']);
Route::get('/posts/{post}', [PostController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Protected post routes
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [PostController::class, 'like']);
    Route::post('/posts/{post}/comment', [PostController::class, 'comment']);

    // User Profile Routes
    Route::get('/user', [UserController::class, 'show']);
    Route::post('/user/update', [UserController::class, 'update']);
    Route::post('/user/password', [UserController::class, 'updatePassword']);
    Route::post('/user/notifications', [UserController::class, 'updateNotifications']);
    Route::get('/user-profile', [UserController::class, 'getProfile']);
    Route::get('/user-stats', [UserController::class, 'getUserStats']);
    Route::get('/user-posts', [UserController::class, 'getUserPosts']);
    Route::get('/user-comments', [UserController::class, 'getUserComments']);
});