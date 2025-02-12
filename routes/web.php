<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/migrate', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate');
    return "Migrate Complete!";
});

Route::get('/restart-queue', function () {
    \Illuminate\Support\Facades\Artisan::call('queue:restart');
    return "Queue restarted!";
});

Route::get('/storage-link', function () {
    Route::get('/storage-link', function () {
        if (app()->environment('production')) {
            abort(403, 'Unauthorized action.');
        }
    
        try {
            Artisan::call('storage:link');
            return response()->json(['message' => 'Storage link created successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});