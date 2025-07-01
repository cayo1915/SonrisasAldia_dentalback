<?php

use App\Http\Controllers\AgendaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChargeController;
use App\Http\Controllers\ClinicalHistoryController;
use App\Http\Controllers\HoraryController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RRHHController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;

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

Route::prefix('v1')->middleware('jwt')->group(function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', [AuthController::class, 'login'])->name('login')->withoutMiddleware(['jwt']);
        Route::post('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh'])->withoutMiddleware(['jwt']);
        Route::post('/resend_mail_code',  [AuthController::class, 'resendMailCode'])->withoutMiddleware(['jwt']);
        Route::post('/reset_credentials_mandatory',  [AuthController::class, 'resetCredentialsMandatory'])->withoutMiddleware(['jwt']);
        Route::post('/reset_credentials',  [AuthController::class, 'resetCredentials']);
        Route::post('/push-token', [AuthController::class, 'updateExpoPushToken']);
        Route::post('/push-notification-status', [AuthController::class, 'setPushNotificationStatus']);
        Route::post('/verify-reset-code', [PasswordResetController::class, 'reset'])->withoutMiddleware(['jwt']);
        Route::get('/validateToken', [AuthController::class, 'validateToken'])->withoutMiddleware(['jwt']);
    });

    Route::group(['prefix' => 'role'], function () {
        Route::get('/', [RoleController::class, 'index'])->withoutMiddleware(['jwt']);
        Route::post('/', [RoleController::class, 'store']);
        Route::patch('/{id}',  [RoleController::class, 'update']);
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/doctors-available', [UserController::class, 'getAvailableDoctors']);
        Route::get('/{id}', [UserController::class, 'getUserById']);
        Route::post('/', [UserController::class, 'store'])->withoutMiddleware(['jwt']);
        Route::post('/generate-verification/{id}', [UserController::class, 'generateUrlVerify'])->withoutMiddleware(['jwt']);
        Route::patch('/{id}', [UserController::class, 'update']);
        Route::patch('/change-role/{id}', [UserController::class, 'changeRole']);
        Route::delete('/{id}', [UserController::class, 'delete']);
    });

    Route::group(['prefix' => 'charge'], function () {
        Route::get('', [ChargeController::class, 'index'])->withoutMiddleware(['jwt']);
        Route::post('/', [ChargeController::class, 'store']);
        Route::patch('/{id}', [ChargeController::class, 'update']);
        Route::delete('/{id}', [ChargeController::class, 'delete']);
    });

    Route::group(['prefix' => 'rrhh'], function () {
        Route::get('', [RRHHController::class, 'index']);
        Route::get('/{id}', [RRHHController::class, 'getRRHHById']);
        Route::post('/', [RRHHController::class, 'store']);
        Route::post('/upload-photo/{id}', [RRHHController::class, 'uploadPhoto']);
        Route::patch('/{id}', [RRHHController::class, 'update']);
        Route::delete('/{id}', [RRHHController::class, 'delete']);
    });

    Route::group(['prefix' => 'agenda'], function () {
        Route::post('/', [AgendaController::class, 'store']);
        Route::patch('/{id}', [AgendaController::class, 'edit']);
    });

    Route::group(['prefix' => 'horary'], function () {
        Route::get('/available-times', [HoraryController::class, 'availableTimes']);
    });

    Route::group(['prefix' => 'reservation'], function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::get('/patients-attended', [ReservationController::class, 'patientsAttended']);
        Route::post('/', [ReservationController::class, 'store']);
        Route::post('/can-reschedule/{id}', [ReservationController::class, 'canReschedule']);
        Route::post('/reschedule/{id}', [ReservationController::class, 'rescheduleReservation']);
        Route::patch('/update-atrributes/{id}', [ReservationController::class, 'updateAtrributes']);
    });

    Route::group(['prefix' => 'clinical-history'], function () {
        Route::get('/', [ClinicalHistoryController::class, 'index']);
        Route::get('/{id}', [ClinicalHistoryController::class, 'listHistoryById']);
        Route::post('/', [ClinicalHistoryController::class, 'create']);
        Route::patch('/{id}', [ClinicalHistoryController::class, 'update']);
        Route::patch('/mark-tooth/{id}', [ClinicalHistoryController::class, 'markTooth']);
        Route::patch('/mark-multiple-teeth/{id}', [ClinicalHistoryController::class, 'markMultipleTeeth']);
    });

    Route::group(['prefix' => 'notification'], function () {
        Route::get('/', [NotificationsController::class, 'index']);
        Route::post('/', [NotificationsController::class, 'create']);
        Route::patch('mark-view/{id}', [NotificationsController::class, 'markNotificationAsViewed']);
        Route::delete('/{id}', [NotificationsController::class, 'delete']);
    });

    Route::group(['prefix' => 'reminder'], function () {
        Route::post('/config', [ReminderController::class, 'createOrUpdateReminderConfig']);
    });

    Route::get('email-verification/{id}/{hash}', [VerificationController::class, 'verify'])
        // ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
});



Route::fallback(function () {
    return response()->json([
        'message' => 'Acceso restringido.'
    ], 404);
});
