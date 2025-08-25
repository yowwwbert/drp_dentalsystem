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

            $branch = Branches::find($pendingAppointment['branch_id'] ?? null);
            $dentist = User::find($pendingAppointment['dentist_id'] ?? null);
            $schedule = Schedule::find($pendingAppointment['schedule_id'] ?? null);
            $treatments = !empty($pendingAppointment['treatment_ids']) && is_array($pendingAppointment['treatment_ids'])
                ? Treatment::whereIn('treatment_id', $pendingAppointment['treatment_ids'])->get()
                    ->map(fn($t) => [
                        'treatment_id' => $t->treatment_id,
                        'treatment_name' => $t->name,
                    ])->toArray()
                : [];

            $appointmentData = [
                'branch_id' => $branch?->branch_id,
                'branch_name' => $branch?->name,
                'dentist_id' => $dentist?->user_id,
                'dentist_name' => $dentist?->name,
                'treatments' => $treatments,
                'schedule' => $schedule ? [
                    'schedule_id' => $schedule->schedule_id,
                    'schedule_date' => $schedule->schedule_date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ] : null,
            ];

            if (!$appointmentData['branch_id'] || !$appointmentData['dentist_id'] || !$appointmentData['treatments'] || !$appointmentData['schedule']) {
                Log::error('Missing appointment data:', $appointmentData);
                return redirect()->route('appointment.show.dentists')
                    ->with('error', 'Some appointment details are missing. Please select again.');
            }

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