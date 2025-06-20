<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PhoneVerificationNotificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Generate 6-digit code
        $code = random_int(100000, 999999);

        return back()->with('status', 'phone-verification-code-sent');
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
