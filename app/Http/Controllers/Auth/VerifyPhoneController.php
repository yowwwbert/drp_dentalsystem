<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VerifyPhoneController extends Controller
{
    /**
     * Mark the authenticated user's phone number as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Log OTP verification attempt
        Log::info('Attempting to verify OTP for phone verification', [
            'user_id' => $user->user_id,
            'phone_number' => $user->phone_number,
            'guardian_phone_number' => $user->user_type === 'Patient' ? ($user->guardian_phone_number ?? 'N/A') : 'N/A',
            'otp' => $request->otp,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Check if phone is already verified
        if ($user->hasVerifiedPhone()) {
            Log::info('Phone already verified', ['user_id' => $user->user_id]);
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        // Validate OTP
        $request->validate(['otp' => 'required|digits:6']);

        $cachedOtp = Cache::get('phone_verification_' . $user->user_id);

        if ($request->otp == $cachedOtp) {
            // Mark phone as verified
            $user->phone_verified_at = now();
            $user->save();

            // Clear cached OTP
            Cache::forget('phone_verification_' . $user->user_id);

            // Fire Verified event
            event(new Verified($user));

            Log::info('Phone verified successfully', [
                'user_id' => $user->user_id,
                'phone_number' => $user->phone_number,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        Log::warning('Invalid OTP provided', [
            'user_id' => $user->user_id,
            'otp' => $request->otp,
            'timestamp' => now()->toDateTimeString(),
        ]);

        return back()->withErrors(['otp' => 'Invalid OTP. Please check the logs and try again.']);
    }
}