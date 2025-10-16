<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\PhoneVerificationNotificationController;
use App\Http\Controllers\Auth\PhoneVerificationPromptController;
use App\Http\Controllers\Auth\VerifyPhoneController;
use App\Http\Controllers\Auth\PatientMedicalInfoController;
use Illuminate\Support\Facades\Route;

// Allow GET /login for everyone so it always logs out and shows the login form
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('/medical-information', [PatientMedicalInfoController::class, 'create'])
        ->name('medical-information');

    Route::post('/medical-information', [PatientMedicalInfoController::class, 'store'])
        ->name('medical-information.store');
});

Route::middleware('auth')->group(function () {
    // New route for owners to register staff
    Route::get('owner/register-staff', [RegisteredUserController::class, 'create'])
        ->name('owner.register-staff');

    Route::post('owner/register-staff', [RegisteredUserController::class, 'storeOwnerStaff']);

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('verify-phone', [PhoneVerificationPromptController::class, '__invoke'])
        ->name('phone.verification.notice');

    Route::post('phone/verify', [VerifyPhoneController::class, '__invoke'])
        ->name('phone.verify.store')
        ->middleware('throttle:6,1');

    Route::post('phone/verification-notification', [PhoneVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('phone.verification.notification');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

?>