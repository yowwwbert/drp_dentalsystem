<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentListController extends Controller
{
    public function index(Request $request)
    {
        // Initialize the query with relationships
        $query = Appointment::with(['patient.user', 'dentist.user', 'branch', 'schedule', 'treatments', 'statusChangedBy']);

        // Apply filters based on query parameters
        if ($request->has('dentist_id') && !empty($request->query('dentist_id'))) {
            $query->where('dentist_id', $request->query('dentist_id'));
        }

        if ($request->has('patient_id') && !empty($request->query('patient_id'))) {
            $query->where('patient_id', $request->query('patient_id'));
        }

        if ($request->has('branch_id') && !empty($request->query('branch_id'))) {
            $query->where('branch_id', $request->query('branch_id'));
        }

        // Fetch and map the appointments
        $appointments = $query->get()
            ->map(function ($appointment) {
                // Log the raw appointment data for debugging
                Log::info('Raw Appointment Data', [
                    'appointment_id' => $appointment->appointment_id,
                    'treatment_ids' => $appointment->treatments->pluck('treatment_id')->toArray(),
                    'treatment_names' => $appointment->treatments->pluck('treatment_name')->toArray(),
                    'reschedule_count' => $appointment->reschedule_count,
                    'status_changed_by' => $appointment->status_changed_by,
                    'reason_for_status_change' => $appointment->reason_for_status_change,
                ]);

                // Prepare services array from treatments relationship
                $services = $appointment->treatments->isNotEmpty()
                    ? $appointment->treatments->pluck('treatment_name')->toArray()
                    : ['General Checkup'];

                // Map all appointment fields and related data
                $appointmentData = [
                    'appointment_id' => $appointment->appointment_id,
                    'patient_id' => $appointment->patient_id ?? 'N/A',
                    'patient_first_name' => $appointment->patient && $appointment->patient->user 
                        ? $appointment->patient->user->first_name 
                        : 'N/A',
                    'patient_last_name' => $appointment->patient && $appointment->patient->user 
                        ? $appointment->patient->user->last_name 
                        : 'N/A',
                    'patient_email' => $appointment->patient && $appointment->patient->user 
                        ? $appointment->patient->user->email 
                        : 'N/A',
                    'patient_phone' => $appointment->patient && $appointment->patient->user 
                        ? $appointment->patient->user->phone 
                        : 'N/A',
                    'balance' => $appointment->patient ? ($appointment->patient->balance ?? 0) : 0,
                    'dentist_id' => $appointment->dentist_id ?? 'N/A',
                    'dentist_first_name' => $appointment->dentist && $appointment->dentist->user 
                        ? $appointment->dentist->user->first_name 
                        : 'N/A',
                    'dentist_last_name' => $appointment->dentist && $appointment->dentist->user 
                        ? $appointment->dentist->user->last_name 
                        : 'N/A',
                    'dentist_email' => $appointment->dentist && $appointment->dentist->user 
                        ? $appointment->dentist->user->email 
                        : 'N/A',
                    'branch_id' => $appointment->branch_id ?? 'N/A',
                    'branch_name' => $appointment->branch ? ($appointment->branch->branch_name ?? 'N/A') : 'N/A',
                    'branch_address' => $appointment->branch ? ($appointment->branch->address ?? 'N/A') : 'N/A',
                    'schedule_id' => $appointment->schedule_id ?? 'N/A',
                    'date' => $appointment->schedule ? ($appointment->schedule->schedule_date ?? 'N/A') : 'N/A',
                    'start_time' => $appointment->schedule && $appointment->schedule->start_time 
                        ? date('H:i:s', strtotime($appointment->schedule->start_time)) 
                        : 'N/A',
                    'end_time' => $appointment->schedule && $appointment->schedule->end_time 
                        ? date('H:i:s', strtotime($appointment->schedule->end_time)) 
                        : 'N/A',
                    'services' => $services,
                    'status' => $appointment->status ?? 'Scheduled',
                    'reschedule_count' => $appointment->reschedule_count ?? 0,
                    'status_changed_by' => [
                        'user_id' => $appointment->status_changed_by ?? 'N/A',
                        'first_name' => $appointment->statusChangedBy ? ($appointment->statusChangedBy->first_name ?? 'N/A') : 'N/A',
                        'last_name' => $appointment->statusChangedBy ? ($appointment->statusChangedBy->last_name ?? 'N/A') : 'N/A',
                        'user_type' => $appointment->statusChangedBy ? ($appointment->statusChangedBy->user_type ?? 'N/A') : 'N/A',
                    ],
                    'reason_for_status_change' => $appointment->reason_for_status_change ?? 'N/A',
                    'billing_id' => $appointment->billing_id ?? null,
                    'notes' => $appointment->notes ?? 'N/A',
                    'created_at' => $appointment->created_at 
                        ? date('F j, Y, g:i A', strtotime($appointment->created_at)) 
                        : 'N/A',
                    'updated_at' => $appointment->updated_at 
                        ? date('F j, Y, g:i A', strtotime($appointment->updated_at)) 
                        : 'N/A',
                    'created_by' => $appointment->created_by ?? 'N/A',
                    'updated_by' => $appointment->updated_by ?? 'N/A',
                    // Add related treatment details
                    'treatments' => $appointment->treatments->map(function ($treatment) {
                        return [
                            'treatment_id' => $treatment->treatment_id,
                            'treatment_name' => $treatment->treatment_name,
                            'description' => $treatment->description ?? 'N/A',
                            'cost' => $treatment->cost ?? 0,
                            'duration' => $treatment->duration ?? 'N/A',
                        ];
                    })->toArray(),
                ];

                // Log what services and additional data are being sent to the frontend
                Log::info('Processed Appointment Data', [
                    'appointment_id' => $appointment->appointment_id,
                    'services_sent' => $services,
                    'reschedule_count' => $appointment->reschedule_count,
                    'status_changed_by' => $appointmentData['status_changed_by'],
                    'reason_for_status_change' => $appointment->reason_for_status_change,
                    'treatments_sent' => $appointmentData['treatments'],
                ]);

                // Log schedule and related fields for extra debugging
                Log::info('Appointment Schedule Data', [
                    'appointment_id' => $appointment->appointment_id,
                    'schedule_id' => $appointment->schedule_id,
                    'schedule' => $appointment->schedule ? $appointment->schedule->toArray() : null,
                    'start_time' => $appointmentData['start_time'],
                    'end_time' => $appointmentData['end_time'],
                    'billing_id' => $appointmentData['billing_id'],
                    'dentist_id' => $appointmentData['dentist_id'],
                    'branch_id' => $appointmentData['branch_id'],
                    'reschedule_count' => $appointmentData['reschedule_count'],
                    'status_changed_by' => $appointmentData['status_changed_by'],
                    'reason_for_status_change' => $appointmentData['reason_for_status_change'],
                ]);

                return $appointmentData;
            });

        Log::info('Appointments JSON Response Preview', [
            'count' => $appointments->count(),
            'first_appointment' => $appointments->first(),
        ]);

        return response()->json(['appointments' => $appointments]);
    }
}