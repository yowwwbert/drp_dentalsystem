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
        $appointments = Appointment::with(['patient.user', 'dentist.user', 'branch', 'schedule'])
            ->get()
            ->map(function ($appointment) {
                $appointmentData = [
                    'appointment_id' => $appointment->appointment_id,
                    'patient' => $appointment->patient && $appointment->patient->user 
                        ? $appointment->patient->user->last_name . ', ' . $appointment->patient->user->first_name 
                        : 'N/A',
                    'patient_id' => $appointment->patient_id ?? 'N/A',
                    'balance' => $appointment->patient ? ($appointment->patient->balance ?? 0) : 0,
                    'date' => $appointment->schedule ? ($appointment->schedule->schedule_date ?? 'N/A') : 'N/A',
                    'time' => $appointment->schedule && $appointment->schedule->start_time 
                        ? date('H:i:s', strtotime($appointment->schedule->start_time)) 
                        : 'N/A',
                    'branch' => $appointment->branch ? ($appointment->branch->branch_name ?? 'N/A') : 'N/A',
                    'services' => $appointment->treatment_name ? (is_array($appointment->treatment_name) ? $appointment->treatment_name : [$appointment->treatment_name]) : ['General Checkup'],
                    'dentist' => $appointment->dentist && $appointment->dentist->user 
                        ? $appointment->dentist->user->last_name . ', ' . $appointment->dentist->user->first_name 
                        : 'N/A',
                    'status' => $appointment->status ?? 'Scheduled',
                ];

                // Log schedule-related data for debugging after formatting
                Log::info('Appointment Schedule Data', [
                    'appointment_id' => $appointment->appointment_id,
                    'schedule_id' => $appointment->schedule_id,
                    'schedule' => $appointment->schedule ? $appointment->schedule->toArray() : null,
                    'start_time' => $appointment->schedule ? ($appointment->schedule->start_time ?? 'null') : 'no schedule',
                    'formatted_time' => $appointmentData['time'],
                ]);

                return $appointmentData;
            });

        return response()->json($appointments);
    }
}