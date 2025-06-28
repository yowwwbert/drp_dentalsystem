<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PhoneVerificationPromptController extends Controller
{
    /**
     * Show the phone verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if (
            !$request->user()->hasVerifiedPhone() &&
            !$request->session()->has('verification_otp_sent')
        ) {
            $request->user()->sendPhoneVerificationNotification();
            $request->session()->put('verification_otp_sent', true);
        }

        return $request->user()->hasVerifiedPhone()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/VerifyPhone', ['status' => $request->session()->get('status')]);
    }
}