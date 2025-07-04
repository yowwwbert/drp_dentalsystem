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

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {

    Route::get('verify-email', EmailVerificationPromptController::class) //Renders the email verification page and sends the verification link
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class) //Check if verified 
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store']) //Resend din
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('verify-phone', [PhoneVerificationPromptController::class, '__invoke']) //Page ng phone verification
        ->name('phone.verification.notice')
        ->middleware('auth');

    Route::post('phone/verify', [VerifyPhoneController::class, '__invoke']) //Check ng OTP
        ->name('phone.verify.store')
        ->middleware(['auth', 'throttle:6,1']);

    Route::post('phone/verification-notification', [PhoneVerificationNotificationController::class, 'store']) //To resend ang OTP sa phone number
        ->middleware(['auth', 'throttle:6,1'])
        ->name('phone.verification.notification');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/medical-information', [PatientMedicalInfoController::class, 'create']) //Page ng medical
        ->name('medical-information');

    Route::post('/medical-information', [PatientMedicalInfoController::class, 'store']) //Save ng medical
        ->name('medical-information.store');
});
