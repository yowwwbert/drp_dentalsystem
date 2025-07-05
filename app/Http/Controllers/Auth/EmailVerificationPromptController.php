<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if (!$user->hasVerifiedEmail() && !$request->session()->has('verification_email_sent')) {
            $user->sendEmailVerificationNotification();
            $request->session()->put('verification_email_sent', true);
        }

        return $user->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : Inertia::render('auth/VerifyEmail', [
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
