<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\AppointmentTreatment;
use App\Models\Clinic\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AppointmentController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('web/Appointment');
    }

    /**
     * Step 1: Store appointment request temporarily in session.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'branch_id'   => 'required|exists:branches,branch_id',
        'dentist_id'  => 'required|exists:users,user_id',
        'schedule_id' => 'required|exists:schedules,schedule_id',
        'treatment_id'=> 'required|exists:treatments,treatment_id',
    ]);

    $request->session()->put('pending_appointment', $validated);

    if (!Auth::check()) {
        return redirect()->route('login')
            ->with('message', 'Please log in or sign up to confirm your appointment.');
    }

    // ✅ Pass appointment to frontend
    return Inertia::render('appointment/ConfirmAppointment', [
        'appointment' => [
            'branch_id'   => $validated['branch_id'],
            'dentist_id'  => $validated['dentist_id'],
            'treatment_id'=> $validated['treatment_id'],
            'schedule'    => \App\Models\Clinic\Schedule::find($validated['schedule_id']),
        ],
    ]);
}


    /**
     * Step 2: Confirm and persist appointment in database.
     */
    public function confirm(Request $request)
    {
        // Must be logged in
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to confirm an appointment.');
        }

        // Must be a patient
        if (Auth::user()->user_type !== 'Patient') {
            return redirect()->route('appointment')
                ->with('error', 'Only patients can book appointments.');
        }

        // Get pending appointment data
        $pending = $request->session()->get('pending_appointment');
        if (!$pending) {
            return redirect()->route('appointment')
                ->with('error', 'No appointment data found. Please try again.');
        }

        try {
            return DB::transaction(function () use ($request, $pending) {
                // Verify schedule availability
                $schedule = Schedule::where('schedule_id', $pending['schedule_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$schedule) {
                    return redirect()->route('appointment')
                        ->with('error', 'Selected schedule is no longer available.');
                }

                // Create appointment
                $appointment = Appointment::create([
                    'patient_id'             => Auth::id(),
                    'dentist_id'             => $pending['dentist_id'],
                    'schedule_id'            => $pending['schedule_id'],
                    'branch_id'              => $pending['branch_id'],
                    'status'                 => 'Scheduled',
                    'status_changed_by'      => Auth::id(),
                    'appointment_created_by' => Auth::id(),
                ]);

                // Attach treatment
                AppointmentTreatment::create([
                    'appointment_id' => $appointment->appointment_id,
                    'treatment_id'   => $pending['treatment_id'],
                ]);

                // Mark schedule as unavailable
                $schedule->update(['is_active' => false]);

                // Clear session
                $request->session()->forget('pending_appointment');

                return redirect()->route('dashboard')
                    ->with('message', 'Appointment booked successfully!');
            });
        } catch (\Exception $e) {
            Log::error('Failed to confirm appointment: ' . $e->getMessage());

            return redirect()->route('appointment')
                ->with('error', 'Failed to confirm appointment. Please try again.');
        }
    }
}
