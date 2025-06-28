<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PhoneVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            Log::info('Phone already verified, skipping OTP resend', [
                'user_id' => $user->user_id,
                'timestamp' => now()->toDateTimeString(),
            ]);
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Log resend attempt
        Log::info('New OTP for', [
            'user_id' => $user->user_id,
        ]);

        try {
            $user->sendPhoneVerificationNotification();
            $request->session()->put('verification_otp_sent', true);
            Log::info('OTP resent successfully', [
                'user_id' => $user->user_id,
                'phone_number' => $user->phone_number,
                'timestamp' => now()->toDateTimeString(),
            ]);
            return back()->with('status', 'phone-verification-code-sent');
        } catch (\Exception $e) {
            Log::error('Failed to resend OTP', [
                'user_id' => $user->user_id,
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString(),
            ]);
            return back()->withErrors(['otp' => 'Failed to resend OTP.']);
        }
    }
    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'verification_code' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $cachedCode = Cache::get("phone_verification_code_{$user->id}");

        if ($request->verification_code == $cachedCode) {
            $user->phone_verified_at = now();
            $user->save();

            Cache::forget("phone_verification_code_{$user->id}");

            return redirect()->intended(route('dashboard', absolute: false))->with('status', 'phone-verified');
        }

        return back()->withErrors(['verification_code' => 'The code is incorrect or expired.']);
    }
}
