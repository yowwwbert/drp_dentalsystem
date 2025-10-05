<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\AppointmentTreatment;
use App\Models\Clinic\Schedule;

class ManageAppointmentController extends Controller
{
    /**
     * Display the manage appointment page
     * URL: /appointments/{appointmentId}/manage
     */
    public function manage($appointmentId)
    {
        return Inertia::render('Dashboard/General/ManageAppointment', [
            'appointmentId' => $appointmentId
        ]);
    }

    /**
     * Get a single appointment details
     * URL: /api/appointments/{appointmentId}
     */
    public function show($appointmentId)
    {
        try {
            // Get appointment with patient info from users table
            $appointment = DB::table('appointments as a')
                ->join('users as patient_user', 'a.patient_id', '=', 'patient_user.user_id')
                ->join('users as dentist_user', 'a.dentist_id', '=', 'dentist_user.user_id')
                ->join('schedules as s', 'a.schedule_id', '=', 's.schedule_id')
                ->join('branches as b', 'a.branch_id', '=', 'b.branch_id')
                ->where('a.appointment_id', $appointmentId)
                ->select(
                    'a.appointment_id',
                    'a.patient_id',
                    'patient_user.first_name as patient_first_name',
                    'patient_user.last_name as patient_last_name',
                    'a.schedule_id as schedule_id',
                    's.start_time',
                    's.end_time',
                    's.schedule_date as date',
                    'a.billing_id',
                    'b.branch_name as branch',
                    'a.branch_id',
                    DB::raw("CONCAT(dentist_user.first_name, ' ', dentist_user.last_name) as dentist"),
                    'a.dentist_id',
                    'a.status',
                    'a.notes',
                    'a.reschedule_count',
                    'a.created_at',
                    'a.updated_at'
                )
                ->first();

            Log::info('Fetched appointment details: ' . json_encode($appointment));

            if (!$appointment) {
                return response()->json(['message' => 'Appointment not found'], 404);
            }

            // Get services/treatments for this appointment
            $services = DB::table('appointment_treatments as at')
                ->join('treatments as t', 'at.treatment_id', '=', 't.treatment_id')
                ->where('at.appointment_id', $appointmentId)
                ->pluck('t.treatment_name')
                ->toArray();

            $appointment->services = $services;

            return response()->json($appointment);
        } catch (\Exception $e) {
            Log::error('Error fetching appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch appointment'], 500);
        }
    }

    /**
     * Reschedule an appointment
     * URL: PUT /api/appointments/{appointmentId}
     */
    public function reschedule(Request $request, $appointmentId)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,schedule_id',
            'treatment_ids' => 'required|array|min:1',
            'treatment_ids.*' => 'exists:treatments,treatment_id',
            'notes' => 'nullable|string',
            'reason' => 'required|string|min:3',
        ]);

        try {
            DB::beginTransaction();

            // Get current user
            $currentUser = Auth::user();

            // Get the appointment
            $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

            // Check if appointment has reached maximum reschedule limit
            if ($appointment->reschedule_count >= 3) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Maximum reschedule limit reached. This appointment has already been rescheduled 3 times.'
                ], 400);
            }

            // Get old schedule to mark it as available again
            $oldSchedule = Schedule::where('schedule_id', $appointment->schedule_id)->first();
            if ($oldSchedule) {
                $oldSchedule->update(['is_active' => true]);
            }

            // Get new schedule details
            $newSchedule = Schedule::where('schedule_id', $request->schedule_id)
                ->where('is_active', true)
                ->firstOrFail();

            if (!$newSchedule) {
                DB::rollBack();
                return response()->json(['message' => 'Selected schedule is not available'], 400);
            }

            // Update appointment
            $appointment->update([
                'schedule_id' => $request->schedule_id,
                'branch_id' => $newSchedule->branch_id,
                'dentist_id' => DB::table('dentist_schedule')
                    ->where('schedule_id', $request->schedule_id)
                    ->value('dentist_id'),
                'notes' => $request->notes,
                'reschedule_count' => $appointment->reschedule_count + 1,
                'status_changed_by' => $currentUser->user_id,
                'status_changed_at' => now(),
                'reason_for_status_change' => $request->reason,
                'updated_at' => now(),
            ]);

            // Delete old treatments
            AppointmentTreatment::where('appointment_id', $appointmentId)->delete();

            // Insert new treatments
            foreach ($request->treatment_ids as $treatmentId) {
                AppointmentTreatment::create([
                    'appointment_id' => $appointmentId,
                    'treatment_id' => $treatmentId,
                ]);
            }

            // Mark new schedule as unavailable
            $newSchedule->update(['is_active' => false]);

            // Log the reschedule (optional)
            if ($request->reason && DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                DB::table('appointment_logs')->insert([
                    'appointment_id' => $appointmentId,
                    'action' => 'rescheduled',
                    'reason' => $request->reason,
                    'user_id' => $currentUser->user_id,
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Appointment rescheduled successfully',
                'appointment' => [
                    'appointment_id' => $appointmentId,
                    'date' => $newSchedule->schedule_date,
                    'start_time' => $newSchedule->start_time,
                    'end_time' => $newSchedule->end_time,
                    'reschedule_count' => $appointment->reschedule_count,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rescheduling appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reschedule appointment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel an appointment
     * URL: POST /api/appointments/{appointmentId}/cancel
     */
    public function cancel(Request $request, $appointmentId)
    {
        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        try {
            DB::beginTransaction();

            // Get current user
            $currentUser = Auth::user();

            // Get the appointment
            $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

            // Check if appointment can be cancelled
            if ($appointment->status === 'Completed' || $appointment->status === 'Cancelled') {
                DB::rollBack();
                return response()->json(['message' => 'This appointment cannot be cancelled'], 400);
            }

            // Update appointment status
            $appointment->update([
                'status' => 'Cancelled',
                'status_changed_by' => $currentUser->user_id,
                'status_changed_at' => now(),
                'reason_for_status_change' => $request->reason,
                'updated_at' => now(),
            ]);

            // Mark the associated schedule as available again
            if ($appointment->schedule_id) {
                Schedule::where('schedule_id', $appointment->schedule_id)
                    ->update(['is_active' => true]);
            }

            // Log the cancellation (optional)
            if (DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                DB::table('appointment_logs')->insert([
                    'appointment_id' => $appointmentId,
                    'action' => 'cancelled',
                    'reason' => $request->reason,
                    'user_id' => $currentUser->user_id,
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Appointment cancelled successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling appointment: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel appointment'], 500);
        }
    }

    /**
     * Update appointment status (for non-patients only)
     * URL: PUT /api/appointments/{appointmentId}/status
     */
    public function updateStatus(Request $request, $appointmentId)
    {
        // Get current user
        $currentUser = Auth::user();

        // Check if user is not a patient
        if ($currentUser->user_type === 'Patient') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:Scheduled,Checked In,Completed,Cancelled,No Show',
        ]);

        try {
            DB::beginTransaction();

            // Get the appointment
            $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

            // Update appointment status
            $appointment->update([
                'status' => $request->status,
                'status_changed_by' => $currentUser->user_id,
                'status_changed_at' => now(),
                'reason_for_status_change' => "Status changed to {$request->status}",
                'updated_at' => now(),
            ]);

            // If status changed to Cancelled or No Show, mark schedule as available
            if (in_array($request->status, ['Cancelled', 'No Show'])) {
                if ($appointment->schedule_id) {
                    Schedule::where('schedule_id', $appointment->schedule_id)
                        ->update(['is_active' => true]);
                }
            }

            // Log the status change (optional)
            if (DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                DB::table('appointment_logs')->insert([
                    'appointment_id' => $appointmentId,
                    'action' => 'status_updated',
                    'reason' => "Status changed to {$request->status}",
                    'user_id' => $currentUser->user_id,
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Appointment status updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating appointment status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update appointment status'], 500);
        }
    }
}