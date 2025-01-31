<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EducationContentController;
use App\Http\Controllers\FollowsHandlerController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
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
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.forgot');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

// Google Authentication Routes
Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
Route::post('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Public post routes
Route::get('/posts', [PostController::class, 'index']);
Route::get('/user_posts', [PostController::class, 'UserPosts']);
Route::get('/user_posts/{id}', [PostController::class, 'UserPostsforotherusers']);
Route::get('/posts/{post}', [PostController::class, 'show']);

Route::get('/users/list', [UserController::class, 'getUsers']);
// Job routes
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{id}', [JobController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/jobs', [JobController::class, 'store']);
    Route::put('/jobs/{id}', [JobController::class, 'update']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
});

// Education Content Routes
Route::get('/education-contents', [EducationContentController::class, 'index']);
Route::get('/education-contents/{id}', [EducationContentController::class, 'show']);

Route::get('/user-profile/{id}', [UserController::class, 'getProfileforotheruser']);
Route::get('/user-stats/{id}', [UserController::class, 'getUserStatsforotheruser']);



Route::middleware('auth:sanctum')->group(function () {
    // User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users', [UserController::class, 'getUsers']);

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

    // Education Content Management Routes
    Route::post('/education-contents', [EducationContentController::class, 'store']);
    Route::put('/education-contents/{id}', [EducationContentController::class, 'update']);
    Route::delete('/education-contents/{id}', [EducationContentController::class, 'destroy']);
    // Category routes
    Route::post('/award-video-points', [EducationContentController::class, 'videoPoints']);
    
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('/follow/{following_id}', [FollowsHandlerController::class, 'toggleFollow']);

    // Message routes
    Route::get('/messages/conversations', [MessageController::class, 'getConversationsList']);
    Route::get('/messages/unread-count', [MessageController::class, 'getUnreadCount']);
    Route::get('/notifications', [NotificationController::class, 'getUnreadNotifications']);
    Route::get('/messages/{otherUserId}', [MessageController::class, 'getMessages']);
    Route::post('/messages/send', [MessageController::class, 'sendMessage']);
    Route::post('/messages/mark-read/{conversationId}', [MessageController::class, 'markRead']);

    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/search', [UserController::class, 'search']);
    });
});