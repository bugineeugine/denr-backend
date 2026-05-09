<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CitizenCharterController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ArchiveController;
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
      Route::middleware(['jwt.verified','jwt.cookie'])->post('/settings', 'updateSettings');
});
// Public: anyone can read comments (validator scanning QR)
Route::prefix('comments')->controller(CommentController::class)->group(function () {
    Route::get('/{pertmiId}', 'getCommentByPermitId');
});
// Protected: only authenticated users can post comments
Route::middleware(['jwt.verified','jwt.cookie'])->prefix('comments')->controller(CommentController::class)->group(function () {
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

// Public permit routes (no auth required)
Route::prefix('permits')->controller(PermitController::class)->group(function () {
    Route::get('/find/{id}', 'findPermitById');
    Route::get('/history/steps/{id}', 'historyApprovedByPermitId');
    Route::post('/{id}/notify-expired', 'notifyExpired');
});

// Public violation reporting (validator scans QR -> reports without admin login)
Route::prefix('violations')->controller(ViolationController::class)->group(function () {
    Route::get('/public/by-permit-no/{permitNo}', 'listPublicByPermitNo');
    Route::post('/public/report/{permitNo}', 'reportPublic');
});

Route::middleware(['jwt.verified','jwt.cookie'])->prefix('permits')->controller(PermitController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'create');
    Route::put('/{id}', 'findAndUpdateById');
    Route::put('/approve/{id}', 'findAndUpdatePermitById');
    Route::delete('/{id}', 'findAndDeleteById');
    Route::get('/{id}', 'getPermitByUserId');
    Route::get('/approval/steps', 'getCitizenCharterForApproval');
    Route::post('/{id}/renew', 'renew');
});



Route::middleware(['jwt.verified','jwt.cookie'])->prefix('citizen-charter')->controller(CitizenCharterController::class)->group(function () {
    Route::post('/', 'create');
    Route::get('/', 'getCitizenCharterByUserById');
    Route::get('/lists', 'getCitizenCharter');
    Route::get('/by-position', 'getCitizenCharterForApproval');
        Route::get('/history-approved', 'historyApproved');
    Route::put('/{id}', 'findAndUpdateById');
});




Route::middleware(['jwt.verified','jwt.cookie'])->prefix('archive')->controller(ArchiveController::class)->group(function () {
    Route::get('/permits', 'permits');
    Route::get('/users', 'users');
    Route::post('/permits/{id}/restore', 'restorePermit');
    Route::post('/users/{id}/restore', 'restoreUser');
    Route::delete('/permits/{id}', 'purgePermit');
    Route::delete('/users/{id}', 'purgeUser');
});

Route::middleware(['jwt.verified','jwt.cookie'])->prefix('notifications')->controller(NotificationController::class)->group(function () {
    Route::get('/', 'index');
    Route::put('/read-all', 'markAllRead');
    Route::put('/{id}/read', 'markRead');
    Route::delete('/{id}', 'destroy');
});

Route::middleware(['jwt.verified','jwt.cookie'])->prefix('violations')->controller(ViolationController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/reports', 'reports');
    Route::get('/by-permit/{permitId}', 'byPermit');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
