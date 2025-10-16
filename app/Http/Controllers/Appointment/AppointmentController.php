<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\AppointmentTreatment;
use App\Models\Clinic\Schedule;
use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppointmentNotification;
use App\Http\Controllers\Controller;

class AppointmentController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('web/Appointment');
    }

    public function store(Request $request)
    {
        Log::info('Received appointment store request:', $request->all());

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
            'branch_name' => 'sometimes|string',
            'branch_address' => 'sometimes|string',
            'dentist_id' => 'required|exists:users,user_id',
            'dentist_name' => 'sometimes|string',
            'schedule_id' => 'required|exists:schedules,schedule_id',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'schedule_date' => 'sometimes|date',
            'treatment_ids' => 'required|array|min:1',
            'treatment_ids.*' => 'exists:treatments,treatment_id',
            'treatment_names' => 'sometimes|array|min:1',
            'treatment_names.*' => 'string',
        ]);

        Log::info('Validated appointment data before confirmation:', $validated);

        $request->session()->put('pending_appointment', $validated);

        Log::info('Stored pending appointment in session before confirmation:', [
            'pending_appointment' => $request->session()->get('pending_appointment'),
        ]);

        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('message', 'Please log in or sign up to confirm your appointment.');
        }

        $schedule = Schedule::find($validated['schedule_id']);

        return Inertia::render('appointment/ConfirmAppointment', [
            'appointment' => [
                'branch_id' => $validated['branch_id'],
                'branch_name' => $validated['branch_name'] ?? null,
                'branch_address' => $validated['branch_address'] ?? null,
                'dentist_id' => $validated['dentist_id'],
                'dentist_name' => $validated['dentist_name'] ?? null,
                'schedule' => $schedule,
                'start_time' => Carbon::parse($schedule->start_time, 'Asia/Manila')->format('h:i A'),
                'end_time' => Carbon::parse($schedule->end_time, 'Asia/Manila')->format('h:i A'),
                'treatment_ids' => $validated['treatment_ids'],
            ],
        ]);
    }

    public function confirm(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'You must be logged in to confirm an appointment.');
        }

        if (Auth::user()->user_type !== 'Patient') {
            return redirect()->route('appointment')
                ->with('error', 'Only patients can book appointments.');
        }

        $pending = $request->session()->get('pending_appointment');
        if (!$pending) {
            return redirect()->route('appointment')
                ->with('error', 'No appointment data found. Please try again.');
        }

        Log::info('Retrieved pending appointment for confirmation:', [
            'pending_appointment' => $pending,
        ]);

        try {
            return DB::transaction(function () use ($request, $pending) {
                $schedule = Schedule::where('schedule_id', $pending['schedule_id'])
                    ->where('is_active', true)
                    ->first();

                if (!$schedule) {
                    return redirect()->route('appointment')
                        ->with('error', 'Selected schedule is no longer available.');
                }

                $appointment = Appointment::create([
                    'patient_id' => Auth::id(),
                    'dentist_id' => $pending['dentist_id'],
                    'schedule_id' => $pending['schedule_id'],
                    'branch_id' => $pending['branch_id'],
                    'status' => 'Scheduled',
                    'status_changed_by' => Auth::id(),
                    'appointment_created_by' => Auth::id(),
                ]);

                foreach ($pending['treatment_ids'] as $treatmentId) {
                    AppointmentTreatment::create([
                        'appointment_id' => $appointment->appointment_id,
                        'treatment_id' => $treatmentId,
                    ]);
                }

                $schedule->update(['is_active' => false]);

                // Notify patient
                $patient = Auth::user();
                Log::info('Preparing to send appointment creation notification to patient:', [
                    'patient_id' => $patient->user_id,
                    'appointment_id' => $appointment->appointment_id,
                ]);
                try {
                    Notification::send($patient, new AppointmentNotification($appointment, 'created'));
                    Log::info('Sent appointment creation notification to patient:', [
                        'patient_id' => $patient->user_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send appointment creation notification to patient:', [
                        'patient_id' => $patient->user_id,
                        'appointment_id' => $appointment->appointment_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Notify dentist
                $dentist = User::find($pending['dentist_id']);
                if ($dentist) {
                    Log::info('Preparing to send appointment creation notification to dentist:', [
                        'dentist_id' => $dentist->user_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    try {
                        Notification::send($dentist, new AppointmentNotification($appointment, 'created'));
                        Log::info('Sent appointment creation notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send appointment creation notification to dentist:', [
                            'dentist_id' => $dentist->user_id,
                            'appointment_id' => $appointment->appointment_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::warning('Dentist not found for notification:', [
                        'dentist_id' => $pending['dentist_id'],
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                }

                // Notify staff at the branch
                try {
                    $staff = User::where('user_type', 'Staff')
                        ->join('user_branch', 'users.user_id', '=', 'user_branch.user_id')
                        ->where('user_branch.branch_id', $pending['branch_id'])
                        ->get();
                    Log::info('Staff query executed for confirmation:', [
                        'branch_id' => $pending['branch_id'],
                        'staff_count' => $staff->count(),
                        'staff_ids' => $staff->pluck('user_id')->toArray(),
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    if ($staff->isNotEmpty()) {
                        Log::info('Preparing to send appointment creation notifications to staff:', [
                            'branch_id' => $pending['branch_id'],
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                        Notification::send($staff, new AppointmentNotification($appointment, 'created'));
                        Log::info('Sent appointment creation notifications to staff:', [
                            'branch_id' => $pending['branch_id'],
                            'staff_ids' => $staff->pluck('user_id')->toArray(),
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    } else {
                        Log::warning('No staff found for branch for confirmation notification:', [
                            'branch_id' => $pending['branch_id'],
                            'appointment_id' => $appointment->appointment_id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to query or notify staff for confirmation:', [
                        'branch_id' => $pending['branch_id'],
                        'appointment_id' => $appointment->appointment_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $request->session()->forget('pending_appointment');

                Log::info('Appointment created successfully:', [
                    'appointment_id' => $appointment->appointment_id,
                    'branch_id' => $pending['branch_id'],
                    'branch_name' => $pending['branch_name'] ?? null,
                    'dentist_id' => $pending['dentist_id'],
                    'dentist_name' => $pending['dentist_name'] ?? null,
                    'schedule_id' => $pending['schedule_id'],
                    'treatment_ids' => $pending['treatment_ids'],
                ]);

                return redirect()->route('dashboard')
                    ->with('message', 'Appointment booked successfully!');
            });
        } catch (\Exception $e) {
            Log::error('Failed to confirm appointment: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);

            return redirect()->route('appointment')
                ->with('error', 'Failed to confirm appointment. Please try again.');
        }
    }

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

}