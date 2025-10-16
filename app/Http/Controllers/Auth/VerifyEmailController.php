<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Clinic\Schedule;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Retrieve pending appointment from session or flashed data
        $pendingAppointment = $request->session()->get('appointment') ?? $request->session()->get('pending_appointment');

        Log::info('Email verification processed:', [
            'user_id' => $request->user()->user_id,
            'pending_appointment' => $pendingAppointment ?? 'None',
        ]);

        if ($request->user()->hasVerifiedEmail()) {
            // If email is already verified, redirect based on pending appointment
            if ($pendingAppointment) {
                $appointmentData = $this->formatAppointmentData($pendingAppointment);
                $request->session()->put('pending_appointment', $pendingAppointment);
                Log::info('Preserved pending appointment after email verification:', [
                    'pending_appointment' => $pendingAppointment,
                ]);
                return redirect()->route('appointment.confirmation')
                    ->with('appointment', $appointmentData)
                    ->with('success', 'Email verified successfully.');
            }
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            /** @var \Illuminate\Contracts\Auth\MustVerifyEmail $user */
            $user = $request->user();
            event(new Verified($user));
            Auth::login($user);
        }

        // If there's a pending appointment, redirect to confirmation
        if ($pendingAppointment) {
            $appointmentData = $this->formatAppointmentData($pendingAppointment);
            $request->session()->put('pending_appointment', $pendingAppointment);
            Log::info('Preserved pending appointment after email verification:', [
                'pending_appointment' => $pendingAppointment,
            ]);
            return redirect()->route('appointment.confirmation')
                ->with('appointment', $appointmentData)
                ->with('success', 'Email verified successfully.');
        }

        // Default redirect to dashboard
        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }

    /**
     * Format appointment data with proper time formatting
     */
    private function formatAppointmentData($pendingAppointment): array
    {
        if (!$pendingAppointment || !isset($pendingAppointment['schedule_id'])) {
            return $pendingAppointment;
        }

        $schedule = Schedule::find($pendingAppointment['schedule_id']);
        if (!$schedule) {
            return $pendingAppointment;
        }

        return [
            'branch_id' => $pendingAppointment['branch_id'],
            'branch_name' => $pendingAppointment['branch_name'] ?? null,
            'dentist_id' => $pendingAppointment['dentist_id'],
            'dentist_name' => $pendingAppointment['dentist_name'] ?? null,
            'schedule_id' => $pendingAppointment['schedule_id'],
            'schedule_date' => $pendingAppointment['schedule_date'] ?? $schedule->schedule_date,
            'start_time' => Carbon::parse($schedule->start_time, 'Asia/Manila')->format('h:i A'),
            'end_time' => Carbon::parse($schedule->end_time, 'Asia/Manila')->format('h:i A'),
            'treatment_ids' => $pendingAppointment['treatment_ids'] ?? [],
            'treatment_names' => $pendingAppointment['treatment_names'] ?? [],
        ];
    }
}