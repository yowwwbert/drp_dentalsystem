<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Appointment\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Pivot\AppointmentTreatment;
use App\Models\Clinic\Schedule;
use App\Models\Clinic\Branches;
use App\Models\Users\User;
use App\Models\Clinic\Treatment;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $pendingAppointment = $request->session()->get('pending_appointment');

        if ($request->user()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($pendingAppointment) {
            $request->session()->put('pending_appointment', $pendingAppointment);
        }

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Inertia\Response
    {
        $pendingAppointment = $request->session()->get('pending_appointment');
        $intendedUrl = $request->session()->get('url.intended', route('dashboard', absolute: false));
        $request->authenticate();
        $request->session()->regenerate();

        if ($pendingAppointment) {
            $request->session()->put('pending_appointment', $pendingAppointment);
            $user = Auth::user();

            if ($user->user_type !== 'Patient') {
                $request->session()->forget('pending_appointment');
                return redirect()->intended($intendedUrl)
                    ->with('error', 'Only patients can book appointments.');
            }

            // Use data directly from session instead of re-fetching to preserve exact values
            $appointmentData = [
                'branch_id' => $pendingAppointment['branch_id'],
                'branch_name' => $pendingAppointment['branch_name'],
                'dentist_id' => $pendingAppointment['dentist_id'],
                'dentist_name' => $pendingAppointment['dentist_name'],
                'schedule_id' => $pendingAppointment['schedule_id'],
                'schedule_time' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('start_time'),
                'schedule_date' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('schedule_date'),
                'treatment_ids' => $pendingAppointment['treatment_ids'] ?? [],
                'treatment_names' => $pendingAppointment['treatment_names'] ?? [],
            ];

            // Validate required data
            if (empty($appointmentData['branch_id']) || empty($appointmentData['dentist_id']) || empty($appointmentData['schedule_id']) || empty($appointmentData['treatment_ids'])) {
                Log::error('Missing appointment data in session:', $pendingAppointment);
                $request->session()->forget('pending_appointment');
                return redirect()->route('appointment.show.dentists')
                    ->with('error', 'Some appointment details are missing. Please select again.');
            }

            // Clear the pending appointment from session after processing
            $request->session()->forget('pending_appointment');

            // Pass the appointment data via flash session for the confirmation page
            return redirect()->route('appointment.confirmation')
                ->with('appointment', $appointmentData);
        }

        return redirect()->intended($intendedUrl);
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}