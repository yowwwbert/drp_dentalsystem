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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        if ($request->session()->has('pending_appointment')) {
            $user = Auth::user();
            if ($user->user_type !== 'Patient') {
                $request->session()->forget('pending_appointment');
                return redirect()->route('dashboard')->with('error', 'Only patients can book appointments.');
            }

            $pendingAppointment = $request->session()->get('pending_appointment');
            $appointment = new Appointment();
            $appointment->patient_id = Auth::id();
            $appointment->dentist_id = $pendingAppointment['dentist_id'];
            $appointment->schedule_id = $pendingAppointment['schedule_id'];
            $appointment->branch_id = $pendingAppointment['branch_id'];
            $appointment->status = 'Scheduled';
            $appointment->status_changed_by = Auth::id();
            $appointment->appointment_created_by = Auth::id();
            $appointment->save();

            // Attach treatment_id to the appointment_treatments pivot table
            $treatment = new AppointmentTreatment();
            $treatment->appointment_id = $appointment->appointment_id;
            $treatment->treatment_id = $pendingAppointment['treatment_id'];
            $treatment->save();

            // Set the schedule's is_active to false
            $schedule = Schedule::find($pendingAppointment['schedule_id']);
            if ($schedule) {
                $schedule->is_active = false;
                $schedule->save();
            }

            $request->session()->forget(['pending_appointment', 'selected_branch_id', 'selected_dentist_id', 'selected_treatment_id']);
            
            return redirect()->route('appointment.success')->with('message', 'Appointment booked successfully!');
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