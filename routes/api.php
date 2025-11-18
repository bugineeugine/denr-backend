<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\UserController;
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

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::middleware(['jwt.verified','jwt.cookie'])->get('/user-data', 'userdata');
});
Route::prefix('comments')->controller(CommentController::class)->group(function () {
    Route::get('/{pertmiId}', 'getCommentByPermitId');
 Route::post('/', 'create');
});

Route::middleware(['jwt.verified','jwt.cookie'])->prefix('dashboard')->controller(DashboardController::class)->group(function () {
    Route::get('/', 'index');
      Route::get('/{userId}', 'permitUserById');
});
Route::post('/register', [AuthController::class, 'register']);
Route::middleware(['jwt.verified','jwt.cookie'])->prefix('users')->controller(UserController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'create');
    Route::get('/{id}', 'findUserById');
    Route::delete('/{id}', 'findAndDeleteUserById');
    Route::put('/{id}', 'findAndUpdateUserById');
});

Route::middleware(['jwt.verified','jwt.cookie'])->prefix('permits')->controller(PermitController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'create');
    Route::put('/{id}', 'findAndUpdateById');
    Route::delete('/{id}', 'findAndDeleteById');
    Route::get('/{id}', 'getPermitByUserId');
    Route::get('/find/{id}', 'findPermitById');
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
