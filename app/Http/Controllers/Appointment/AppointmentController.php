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

     public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'      => 'required|exists:branches,branch_id',
            'dentist_id'     => 'required|exists:users,user_id',
            'schedule_id'    => 'required|exists:schedules,schedule_id',
            'treatment_ids'  => 'required|array|min:1',
            'treatment_ids.*'=> 'exists:treatments,treatment_id',
        ]);

        // Save pending appointment in session
        $request->session()->put('pending_appointment', $validated);

        // If not logged in → redirect to login
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('message', 'Please log in or sign up to confirm your appointment.');
        }

        // ✅ Pass appointment data to frontend for confirmation
        return Inertia::render('appointment/ConfirmAppointment', [
            'appointment' => [
                'branch_id'     => $validated['branch_id'],
                'dentist_id'    => $validated['dentist_id'],
                'schedule'      => Schedule::find($validated['schedule_id']),
                'treatment_ids' => $validated['treatment_ids'], // multiple
            ],
        ]);
    }

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

                // Attach multiple treatments
                foreach ($pending['treatment_ids'] as $treatmentId) {
                    AppointmentTreatment::create([
                        'appointment_id' => $appointment->appointment_id,
                        'treatment_id'   => $treatmentId,
                    ]);
                }

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
    /**
     * Return appointments for logged in patient
     */
    public function getAppointments(Request $request)
    {
        $appointments = Appointment::with(['dentist', 'branch', 'schedule', 'treatments'])
            ->where('patient_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($appt) {
                return [
                    'appointment_id' => $appt->appointment_id,
                    'date' => optional($appt->schedule)->date,
                    'time' => optional($appt->schedule)->time,
                    'branch' => optional($appt->branch)->name ?? 'N/A',
                    'dentist' => optional($appt->dentist)->full_name ?? 'N/A',
                    'services' => $appt->treatments->pluck('name')->toArray(),
                    'status' => $appt->status,
                    'notes' => $appt->notes,
                    'created_at' => $appt->created_at->toDateTimeString(),
                    'updated_at' => $appt->updated_at->toDateTimeString(),
                ];
            });

        return response()->json(['appointments' => $appointments]);
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, $appointmentId)
    {
        $appointment = Appointment::where('appointment_id', $appointmentId)
            ->where('patient_id', Auth::id())
            ->firstOrFail();

        if ($appointment->status !== 'Scheduled') {
            return response()->json(['message' => 'Only scheduled appointments can be cancelled.'], 400);
        }

        try {
            return DB::transaction(function () use ($appointment, $request) {
                // Update appointment status
                $appointment->update([
                    'status' => 'Cancelled',
                    'status_changed_by' => Auth::id(),
                    'status_changed_at' => now(),
                    'reason_for_status_change' => $request->input('reason', 'Cancelled by patient'),
                ]);

                // Mark the associated schedule as available
                if ($appointment->schedule) {
                    $appointment->schedule->update(['is_active' => true]);
                }

                return response()->json(['message' => 'Appointment cancelled successfully.']);
            });
        } catch (\Exception $e) {
            Log::error('Failed to cancel appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel appointment. Please try again.'], 500);
        }
    }

    /**
     * Reschedule an appointment
     */
    public function reschedule(Request $request, $appointmentId)
{
    $validated = $request->validate([
        'schedule_id' => 'required|exists:schedules,schedule_id',
        'treatment_ids' => 'required|array|min:1',
        'treatment_ids.*' => 'exists:treatments,id',
        'notes' => 'nullable|string',
        'reason' => 'nullable|string',
    ]);

    $appointment = Appointment::where('appointment_id', $appointmentId)
        ->where('patient_id', Auth::id())
        ->firstOrFail();

    if ($appointment->status !== 'Scheduled') {
        return response()->json(['message' => 'Only scheduled appointments can be rescheduled.'], 400);
    }

    $newSchedule = Schedule::where('schedule_id', $validated['schedule_id'])
        ->where('is_active', true)
        ->firstOrFail();

    DB::transaction(function () use ($appointment, $newSchedule, $validated) {
        if ($appointment->schedule) {
            $appointment->schedule->update(['is_active' => true]);
        }

        $appointment->update([
            'schedule_id' => $validated['schedule_id'],
            'notes' => $validated['notes'],
            'status_changed_by' => Auth::id(),
            'status_changed_at' => now(),
            'reason_for_status_change' => $validated['reason'] ?? 'Rescheduled by patient',
        ]);

        AppointmentTreatment::where('appointment_id', $appointment->id)->delete();
        foreach ($validated['treatment_ids'] as $treatmentId) {
            AppointmentTreatment::create([
                'appointment_id' => $appointment->id,
                'treatment_id' => $treatmentId,
            ]);
        }

        $newSchedule->update(['is_active' => false]);
    });

    $updatedAppointment = Appointment::with(['dentist', 'branch', 'schedule', 'treatments'])
        ->where('appointment_id', $appointmentId)
        ->first();

    return response()->json([
        'message' => 'Appointment rescheduled successfully.',
        'appointment' => [
            'appointment_id' => $updatedAppointment->appointment_id,
            'date' => optional($updatedAppointment->schedule)->date,
            'time' => optional($updatedAppointment->schedule)->time,
            'branch' => optional($updatedAppointment->branch)->name ?? 'N/A',
            'dentist' => optional($updatedAppointment->dentist)->full_name ?? 'N/A',
            'services' => $updatedAppointment->treatments->pluck('name')->toArray(),
            'status' => $updatedAppointment->status,
            'notes' => $updatedAppointment->notes,
            'created_at' => $updatedAppointment->created_at->toDateTimeString(),
            'updated_at' => $updatedAppointment->updated_at->toDateTimeString(),
        ]
    ]);
}

}