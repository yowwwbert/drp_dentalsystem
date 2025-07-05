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

        $user = $request->user();

        if (
            !$request->user()->hasVerifiedPhone() &&
            !$request->session()->has('verification_otp_sent')
        ) {
            $request->user()->sendPhoneVerificationNotification();
            $request->session()->put('verification_otp_sent', true);
        }

        return $request->user()->hasVerifiedPhone()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/VerifyPhone', [
                'status' => $request->session()->get('status'),
                'has_email' => !empty($user->email_address),
                'has_phone' => !empty($user->user_type === 'Patient' && $user->age < 18 && $user->guardian_phone_number
                    ? $user->guardian_phone_number
                    : $user->phone_number),
                'phone_number' => $user->user_type === 'Patient' && $user->age < 18 && $user->guardian_phone_number
                    ? $user->guardian_phone_number
                    : $user->phone_number,
                'email_address' => $user->email_address,
            ]);
    }
}