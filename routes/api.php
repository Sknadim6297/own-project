<?php

use App\Http\Controllers\Api\ProfessionalAvailabilityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExpertBookingController;
use App\Http\Controllers\Api\V1\ExpertController;
use App\Http\Controllers\Api\V1\MyActivityController;
use App\Http\Controllers\Api\V1\MyBookingsController;
use App\Http\Controllers\Api\V1\MyNotificationsController;
use App\Http\Controllers\Api\V1\MyPsychologyController;
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

        // My Space — the signed-in customer's own website records. The only writes are new
        // mood/journal/gratitude entries, saved through the same recorders as the website.
        Route::prefix('my')->group(function () {
            Route::get('/bookings', [MyBookingsController::class, 'index']);
            Route::get('/activity/summary', [MyActivityController::class, 'summary']);
            Route::get('/mood-checkins', [MyActivityController::class, 'moods']);
            Route::get('/journal-entries', [MyActivityController::class, 'journal']);
            Route::get('/gratitude-entries', [MyActivityController::class, 'gratitude']);
            Route::get('/notifications', [MyNotificationsController::class, 'index']);
            Route::post('/notifications/{notificationId}/read', [MyNotificationsController::class, 'markRead'])
                ->where('notificationId', '[0-9a-fA-F-]{36}');
            Route::get('/psychology/options', [MyPsychologyController::class, 'options']);
            Route::middleware('throttle:30,1')->group(function () {
                Route::post('/mood-checkins', [MyPsychologyController::class, 'storeMood']);
                Route::post('/journal-entries', [MyPsychologyController::class, 'storeJournal']);
                Route::post('/gratitude-entries', [MyPsychologyController::class, 'storeGratitude']);
            });
        });

        // Expert consultation booking — website flow, priced and verified on the server.
        Route::prefix('bookings')->middleware('throttle:30,1')->group(function () {
            Route::post('/checkout', [ExpertBookingController::class, 'checkout']);
            Route::post('/order', [ExpertBookingController::class, 'order']);
            Route::post('/verify', [ExpertBookingController::class, 'verify']);
            Route::post('/payment-failed', [ExpertBookingController::class, 'paymentFailed']);
            Route::get('/{booking}/questions', [ExpertBookingController::class, 'questions'])->whereNumber('booking');
            Route::post('/{booking}/answers', [ExpertBookingController::class, 'answers'])->whereNumber('booking');
        });
    });

    // Public catalogue (same DB rows as website); write actions stay authenticated above.
    Route::get('/tools', [ToolController::class, 'index']);
    Route::get('/tools/{identifier}', [ToolController::class, 'show']);
    Route::get('/tool-categories', [ToolController::class, 'categories']);
    Route::get('/experts', [ExpertController::class, 'index']);
    Route::get('/experts/{id}', [ExpertController::class, 'show'])->whereNumber('id');
    Route::get('/experts/{id}/availability', [ExpertController::class, 'availability'])->whereNumber('id');
    Route::get('/programme-events', [ProgrammeEventController::class, 'index']);
});
