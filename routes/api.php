<?php

use App\Http\Controllers\Api\ProfessionalAvailabilityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExpertController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ProgrammeEventController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RegistrationController;
use App\Http\Controllers\Api\V1\ToolController;
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

// Professional Availability API routes for frontend booking
Route::prefix('professional/{professionalId}')->group(function () {
    Route::get('/availability', [ProfessionalAvailabilityController::class, 'getAvailability']);
    Route::get('/available-dates', [ProfessionalAvailabilityController::class, 'getAvailableDates']);
    Route::get('/available-slots', [ProfessionalAvailabilityController::class, 'getAvailableSlots']);
});

/*
|--------------------------------------------------------------------------
| Mobile Sanctum API (v1) — same users DB as website session auth
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);

        Route::prefix('register')->group(function () {
            Route::post('/save-lead', [RegistrationController::class, 'saveLead']);
            Route::post('/send-otp', [RegistrationController::class, 'sendOtp']);
            Route::post('/verify-otp', [RegistrationController::class, 'verifyOtp']);
            Route::post('/create-incomplete', [RegistrationController::class, 'createIncomplete']);
            Route::post('/complete', [RegistrationController::class, 'complete']);
        });

        Route::middleware(['auth:sanctum', 'api.active.session'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'api.active.session'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::patch('/profile', [ProfileController::class, 'update']);
    });

    // Public catalogue (same DB rows as website); write actions stay authenticated above.
    Route::get('/tools', [ToolController::class, 'index']);
    Route::get('/tools/{identifier}', [ToolController::class, 'show']);
    Route::get('/tool-categories', [ToolController::class, 'categories']);
    Route::get('/experts', [ExpertController::class, 'index']);
    Route::get('/experts/{id}', [ExpertController::class, 'show'])->whereNumber('id');
    Route::get('/programme-events', [ProgrammeEventController::class, 'index']);
});
