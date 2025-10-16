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
use App\Models\Users\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppointmentNotification;

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
        Log::info('Received reschedule request:', [
            'appointment_id' => $appointmentId,
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'schedule_id' => 'required|exists:schedules,schedule_id',
            'treatment_ids' => 'required|array|min:1',
            'treatment_ids.*' => 'exists:treatments,treatment_id',
            'notes' => 'nullable|string',
            'reason' => 'required|string|min:3',
        ]);

        Log::info('Validated reschedule data:', [
            'appointment_id' => $appointmentId,
            'validated_data' => $request->all(),
        ]);

        try {
            return DB::transaction(function () use ($request, $appointmentId) {
                // Get current user
                $currentUser = Auth::user();

                Log::info('Current user for rescheduling:', [
                    'user_id' => $currentUser->user_id,
                    'user_type' => $currentUser->user_type,
                    'appointment_id' => $appointmentId,
                ]);

                // Get the appointment
                $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

                Log::info('Found appointment for rescheduling:', [
                    'appointment_id' => $appointment->appointment_id,
                    'patient_id' => $appointment->patient_id,
                    'status' => $appointment->status,
                    'current_schedule_id' => $appointment->schedule_id,
                ]);

                // Check if appointment has reached maximum reschedule limit
                if ($appointment->reschedule_count >= 3) {
                    Log::warning('Maximum reschedule limit reached:', [
                        'appointment_id' => $appointmentId,
                        'reschedule_count' => $appointment->reschedule_count,
                    ]);
                    return response()->json([
                        'message' => 'Maximum reschedule limit reached. This appointment has already been rescheduled 3 times.'
                    ], 400);
                }

                // Get new schedule details
                $newSchedule = Schedule::where('schedule_id', $request->schedule_id)
                    ->where('is_active', true)
                    ->firstOrFail();

                Log::info('Found new schedule for rescheduling:', [
                    'schedule_id' => $newSchedule->schedule_id,
                    'start_time' => $newSchedule->start_time,
                    'end_time' => $newSchedule->end_time,
                    'date' => $newSchedule->schedule_date,
                    'is_active' => $newSchedule->is_active,
                ]);

                // Fetch new treatment names
                $newTreatments = DB::table('treatments')
                    ->whereIn('treatment_id', $request->treatment_ids)
                    ->pluck('treatment_name')
                    ->toArray();

                // Notify patient
                $patient = User::find($appointment->patient_id);
                if ($patient) {
                    Log::info('Preparing to queue appointment reschedule notification to patient:', [
                        'patient_id' => $patient->user_id,
                        'appointment_id' => $appointment->appointment_id,
                        'reason' => $request->reason,
                    ]);
                    try {
                        Notification::send($patient, new AppointmentNotification($appointment, 'rescheduled', $request->reason));
                        Log::info('Queued appointment reschedule notification to patient:', [
                            'patient_id' => $patient->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to queue appointment reschedule notification to patient:', [
                            'patient_id' => $patient->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Rollback transaction
                    }
                } else {
                    Log::warning('Patient not found for reschedule notification:', [
                        'patient_id' => $appointment->patient_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }

                // Notify dentist
                $dentist = User::find($appointment->dentist_id);
                if ($dentist) {
                    Log::info('Preparing to queue appointment reschedule notification to dentist:', [
                        'dentist_id' => $dentist->user_id,
                        'appointment_id' => $appointment->appointment_id,
                        'reason' => $request->reason,
                    ]);
                    try {
                        Notification::send($dentist, new AppointmentNotification($appointment, 'rescheduled', $request->reason));
                        Log::info('Queued appointment reschedule notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to queue appointment reschedule notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Rollback transaction
                    }
                } else {
                    Log::warning('Dentist not found for reschedule notification:', [
                        'dentist_id' => $appointment->dentist_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }

                // Notify staff
                try {
                    $staff = User::where('user_type', 'Staff')
                        ->join('user_branch', 'users.user_id', '=', 'user_branch.user_id')
                        ->where('user_branch.branch_id', $appointment->branch_id)
                        ->get();
                    Log::info('Staff query executed for rescheduling:', [
                        'branch_id' => $appointment->branch_id,
                        'staff_count' => $staff->count(),
                        'staff_ids' => $staff->pluck('user_id')->toArray(),
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    if ($staff->isNotEmpty()) {
                        Log::info('Preparing to queue appointment reschedule notifications to staff:', [
                            'branch_id' => $appointment->branch_id,
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                        Notification::send($staff, new AppointmentNotification($appointment, 'rescheduled', $request->reason));
                        Log::info('Queued appointment reschedule notifications to staff:', [
                            'branch_id' => $appointment->branch_id,
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } else {
                        Log::warning('No staff found for branch for reschedule notification:', [
                            'branch_id' => $appointment->branch_id,
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to query or queue staff notifications for rescheduling:', [
                        'branch_id' => $appointment->branch_id,
                        'appointment_id' => $appointment->appointment_id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e; // Rollback transaction
                }

                // Get old schedule to mark it as available again
                $oldSchedule = Schedule::where('schedule_id', $appointment->schedule_id)->first();
                if ($oldSchedule) {
                    Log::info('Updating previous schedule to active:', [
                        'schedule_id' => $oldSchedule->schedule_id,
                        'appointment_id' => $appointmentId,
                    ]);
                    $oldSchedule->update(['is_active' => true]);
                }

                // Update appointment
                Log::info('Updating appointment:', [
                    'appointment_id' => $appointmentId,
                    'new_schedule_id' => $request->schedule_id,
                    'notes' => $request->notes,
                    'reason' => $request->reason,
                ]);

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
                Log::info('Deleting existing treatments:', [
                    'appointment_id' => $appointmentId,
                ]);
                AppointmentTreatment::where('appointment_id', $appointmentId)->delete();

                // Insert new treatments
                Log::info('Creating new treatments:', [
                    'appointment_id' => $appointmentId,
                    'treatment_ids' => $request->treatment_ids,
                ]);
                foreach ($request->treatment_ids as $treatmentId) {
                    AppointmentTreatment::create([
                        'appointment_id' => $appointmentId,
                        'treatment_id' => $treatmentId,
                    ]);
                }

                // Mark new schedule as unavailable
                Log::info('Updating new schedule to inactive:', [
                    'schedule_id' => $newSchedule->schedule_id,
                    'appointment_id' => $appointmentId,
                ]);
                $newSchedule->update(['is_active' => false]);

                // Log the reschedule
                if ($request->reason && DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                    Log::info('Logging reschedule action:', [
                        'appointment_id' => $appointmentId,
                        'reason' => $request->reason,
                    ]);
                    DB::table('appointment_logs')->insert([
                        'appointment_id' => $appointmentId,
                        'action' => 'rescheduled',
                        'reason' => $request->reason,
                        'user_id' => $currentUser->user_id,
                        'created_at' => now(),
                    ]);
                }

                Log::info('Appointment rescheduled successfully:', [
                    'appointment_id' => $appointmentId,
                    'new_schedule_id' => $request->schedule_id,
                    'reschedule_count' => $appointment->reschedule_count + 1,
                    'reason' => $request->reason,
                ]);

                return response()->json([
                    'message' => 'Appointment rescheduled successfully',
                    'appointment' => [
                        'appointment_id' => $appointmentId,
                        'date' => $newSchedule->schedule_date,
                        'start_time' => $newSchedule->start_time,
                        'end_time' => $newSchedule->end_time,
                        'reschedule_count' => $appointment->reschedule_count + 1,
                    ]
                ]);
            });
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rescheduling appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to reschedule appointment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel an appointment
     * URL: POST /api/appointments/{appointmentId}/cancel
     */
    public function cancel(Request $request, $appointmentId)
    {
        Log::info('Received cancel request:', [
            'appointment_id' => $appointmentId,
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        Log::info('Validated cancel data:', [
            'appointment_id' => $appointmentId,
            'reason' => $request->reason,
        ]);

        try {
            return DB::transaction(function () use ($request, $appointmentId) {
                // Get current user
                $currentUser = Auth::user();

                Log::info('Current user for cancellation:', [
                    'user_id' => $currentUser->user_id,
                    'user_type' => $currentUser->user_type,
                    'appointment_id' => $appointmentId,
                ]);

                // Get the appointment
                $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

                Log::info('Found appointment for cancellation:', [
                    'appointment_id' => $appointment->appointment_id,
                    'patient_id' => $appointment->patient_id,
                    'status' => $appointment->status,
                ]);

                // Check if appointment can be cancelled
                if ($appointment->status === 'Completed' || $appointment->status === 'Cancelled') {
                    Log::warning('Attempted to cancel non-cancellable appointment:', [
                        'appointment_id' => $appointmentId,
                        'status' => $appointment->status,
                    ]);
                    return response()->json(['message' => 'This appointment cannot be cancelled'], 400);
                }

                // Notify patient
                $patient = User::find($appointment->patient_id);
                if ($patient) {
                    Log::info('Preparing to queue appointment cancellation notification to patient:', [
                        'patient_id' => $patient->user_id,
                        'appointment_id' => $appointment->appointment_id,
                        'reason' => $request->reason,
                    ]);
                    try {
                        Notification::send($patient, new AppointmentNotification($appointment, 'cancelled', $request->reason));
                        Log::info('Queued appointment cancellation notification to patient:', [
                            'patient_id' => $patient->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to queue appointment cancellation notification to patient:', [
                            'patient_id' => $patient->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Rollback transaction
                    }
                } else {
                    Log::warning('Patient not found for cancellation notification:', [
                        'patient_id' => $appointment->patient_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }

                // Notify dentist
                $dentist = User::find($appointment->dentist_id);
                if ($dentist) {
                    Log::info('Preparing to queue appointment cancellation notification to dentist:', [
                        'dentist_id' => $dentist->user_id,
                        'appointment_id' => $appointment->appointment_id,
                        'reason' => $request->reason,
                    ]);
                    try {
                        Notification::send($dentist, new AppointmentNotification($appointment, 'cancelled', $request->reason));
                        Log::info('Queued appointment cancellation notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to queue appointment cancellation notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e; // Rollback transaction
                    }
                } else {
                    Log::warning('Dentist not found for cancellation notification:', [
                        'dentist_id' => $appointment->dentist_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }

                // Notify staff
                try {
                    $staff = User::where('user_type', 'Staff')
                        ->join('user_branch', 'users.user_id', '=', 'user_branch.user_id')
                        ->where('user_branch.branch_id', $appointment->branch_id)
                        ->get();
                    Log::info('Staff query executed for cancellation:', [
                        'branch_id' => $appointment->branch_id,
                        'staff_count' => $staff->count(),
                        'staff_ids' => $staff->pluck('user_id')->toArray(),
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    if ($staff->isNotEmpty()) {
                        Log::info('Preparing to queue appointment cancellation notifications to staff:', [
                            'branch_id' => $appointment->branch_id,
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                        Notification::send($staff, new AppointmentNotification($appointment, 'cancelled', $request->reason));
                        Log::info('Queued appointment cancellation notifications to staff:', [
                            'branch_id' => $appointment->branch_id,
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                    } else {
                        Log::warning('No staff found for branch for cancellation notification:', [
                            'branch_id' => $appointment->branch_id,
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to query or queue staff notifications for cancellation:', [
                        'branch_id' => $appointment->branch_id,
                        'appointment_id' => $appointment->appointment_id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e; // Rollback transaction
                }

                // Update appointment status
                Log::info('Updating appointment to cancelled:', [
                    'appointment_id' => $appointmentId,
                    'reason' => $request->reason,
                ]);
                $appointment->update([
                    'status' => 'Cancelled',
                    'status_changed_by' => $currentUser->user_id,
                    'status_changed_at' => now(),
                    'reason_for_status_change' => $request->reason,
                    'updated_at' => now(),
                ]);

                // Mark the associated schedule as available again
                if ($appointment->schedule_id) {
                    Log::info('Updating schedule to active:', [
                        'schedule_id' => $appointment->schedule_id,
                        'appointment_id' => $appointmentId,
                    ]);
                    Schedule::where('schedule_id', $appointment->schedule_id)
                        ->update(['is_active' => true]);
                }

                // Log the cancellation
                if (DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                    Log::info('Logging cancellation action:', [
                        'appointment_id' => $appointmentId,
                        'reason' => $request->reason,
                    ]);
                    DB::table('appointment_logs')->insert([
                        'appointment_id' => $appointmentId,
                        'action' => 'cancelled',
                        'reason' => $request->reason,
                        'user_id' => $currentUser->user_id,
                        'created_at' => now(),
                    ]);
                }

                Log::info('Appointment cancelled successfully:', [
                    'appointment_id' => $appointmentId,
                    'status' => 'Cancelled',
                    'reason' => $request->reason,
                ]);

                return response()->json([
                    'message' => 'Appointment cancelled successfully',
                ]);
            });
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling appointment: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e->getTraceAsString(),
            ]);
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
            'reason' => 'required_if:status,Cancelled,No Show|string|min:3',
        ]);

        try {
            return DB::transaction(function () use ($request, $appointmentId, $currentUser) {
                // Get the appointment
                $appointment = Appointment::where('appointment_id', $appointmentId)->firstOrFail();

                Log::info('Found appointment for status update:', [
                    'appointment_id' => $appointment->appointment_id,
                    'patient_id' => $appointment->patient_id,
                    'status' => $appointment->status,
                    'new_status' => $request->status,
                    'reason' => $request->reason,
                ]);

                // Notify patient and dentist for No Show status
                if ($request->status === 'No Show') {
                    $patient = User::find($appointment->patient_id);
                    if ($patient) {
                        Log::info('Preparing to queue no show notification to patient:', [
                            'patient_id' => $patient->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                        try {
                            Notification::send($patient, new AppointmentNotification($appointment, 'no show', $request->reason));
                            Log::info('Queued no show notification to patient:', [
                                'patient_id' => $patient->user_id,
                                'appointment_id' => $appointment->appointment_id,
                                'reason' => $request->reason,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to queue no show notification to patient:', [
                                'patient_id' => $patient->user_id,
                                'appointment_id' => $appointment->appointment_id,
                                'reason' => $request->reason,
                                'error' => $e->getMessage(),
                            ]);
                            throw $e; // Rollback transaction
                        }
                    } else {
                        Log::warning('Patient not found for no show notification:', [
                            'patient_id' => $appointment->patient_id,
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    }

                    $dentist = User::find($appointment->dentist_id);
                    if ($dentist) {
                        Log::info('Preparing to queue no show notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'reason' => $request->reason,
                        ]);
                        try {
                            Notification::send($dentist, new AppointmentNotification($appointment, 'no show', $request->reason));
                            Log::info('Queued no show notification to dentist:', [
                                'dentist_id' => $dentist->user_id,
                                'appointment_id' => $appointment->appointment_id,
                                'reason' => $request->reason,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to queue no show notification to dentist:', [
                                'dentist_id' => $dentist->user_id,
                                'appointment_id' => $appointment->appointment_id,
                                'reason' => $request->reason,
                                'error' => $e->getMessage(),
                            ]);
                            throw $e; // Rollback transaction
                        }
                    } else {
                        Log::warning('Dentist not found for no show notification:', [
                            'dentist_id' => $appointment->dentist_id,
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    }
                }

                // Update appointment status
                Log::info('Updating appointment status:', [
                    'appointment_id' => $appointmentId,
                    'new_status' => $request->status,
                    'reason' => $request->reason,
                ]);
                $appointment->update([
                    'status' => $request->status,
                    'status_changed_by' => $currentUser->user_id,
                    'status_changed_at' => now(),
                    'reason_for_status_change' => $request->reason ?? "Status changed to {$request->status}",
                    'updated_at' => now(),
                ]);

                // If status changed to Cancelled or No Show, mark schedule as available
                if (in_array($request->status, ['Cancelled', 'No Show'])) {
                    if ($appointment->schedule_id) {
                        Log::info('Updating schedule to active:', [
                            'schedule_id' => $appointment->schedule_id,
                            'appointment_id' => $appointmentId,
                        ]);
                        Schedule::where('schedule_id', $appointment->schedule_id)
                            ->update(['is_active' => true]);
                    }
                }

                // Log the status change
                if (DB::getSchemaBuilder()->hasTable('appointment_logs')) {
                    Log::info('Logging status change action:', [
                        'appointment_id' => $appointmentId,
                        'new_status' => $request->status,
                        'reason' => $request->reason,
                    ]);
                    DB::table('appointment_logs')->insert([
                        'appointment_id' => $appointmentId,
                        'action' => 'status_updated',
                        'reason' => $request->reason ?? "Status changed to {$request->status}",
                        'user_id' => $currentUser->user_id,
                        'created_at' => now(),
                    ]);
                }

                Log::info('Appointment status updated successfully:', [
                    'appointment_id' => $appointmentId,
                    'status' => $request->status,
                    'reason' => $request->reason,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Appointment status updated successfully',
                ]);
            });
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating appointment status: ' . $e->getMessage(), [
                'appointment_id' => $appointmentId,
                'exception' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Failed to update appointment status'], 500);
        }
    }
}