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


class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        // Always log out the user if they are authenticated
        if ($request->user()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
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
    $request->authenticate();
    $request->session()->regenerate();

    // If there's a pending appointment, redirect to confirmation
    if ($request->session()->has('pending_appointment')) {
        $user = Auth::user();

        if ($user->user_type !== 'Patient') {
            $request->session()->forget('pending_appointment');
            return redirect()->route('dashboard')
                ->with('error', 'Only patients can book appointments.');
        }

        // Retrieve appointment details from session
        $appointment = $request->session()->pull('pending_appointment');

        // Load related models for richer details
        $branch    = Branches::find($appointment['branch_id']);
        $dentist   = User::find($appointment['dentist_id']);
        $treatment = Treatment::find($appointment['treatment_id']);
        $schedule  = Schedule::find($appointment['schedule_id']);

        // Build enriched appointment object for Vue
        $appointmentData = [
            'branch_id'   => $branch->branch_id,
            'branch_name' => $branch->name ?? null,

            'dentist_id'   => $dentist->user_id,
            'dentist_name' => $dentist->name ?? null,

            'treatment_id'   => $treatment->treatment_id,
            'treatment_name' => $treatment->name ?? null,

            'schedule' => [
                'schedule_id'   => $schedule->schedule_id,
                'schedule_date' => $schedule->schedule_date,
                'start_time'    => $schedule->start_time,
                'end_time'      => $schedule->end_time,
            ],
        ];

        return inertia('appointment/ConfirmAppointment', [
            'appointment' => $appointmentData,
        ]);
    }

    return redirect()->intended(route('dashboard', absolute: false));
}


    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}