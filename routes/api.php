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

Route::post('/login',[AuthController::class,'login'])->name('login');
Route::post('/register',[AuthController::class,'register'])->name('register');

// Public post routes
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Protected post routes
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [PostController::class, 'like']);
    Route::post('/posts/{post}/comment', [PostController::class, 'comment']);
});

// User Profile Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::post('/user/update', [UserController::class, 'update']);
    Route::post('/user/password', [UserController::class, 'updatePassword']);
    Route::post('/user/notifications', [UserController::class, 'updateNotifications']);
});