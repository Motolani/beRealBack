<?php

use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [UsersController::class, 'register']);
Route::post('/login', [UsersController::class, 'login']);
Route::group(['middleware' => 'auth:sanctum'], function()
{
    Route::get('/getDuration', [SettingsController::class, 'durationOptions']);
    Route::resource('schedule', ScheduleController::class);
    Route::post('/test', [ScheduleController::class, 'test']);
    Route::post('/createGroup', [ContactsController::class, 'createGroup']);
    Route::get('/getGroups', [ContactsController::class, 'getGroups']);

});

