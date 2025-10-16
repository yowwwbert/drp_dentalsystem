<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Clinic\Schedule;
use App\Models\Clinic\Branches;
use App\Models\Users\User;
use App\Models\Users\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function data(Request $request)
    {
        try {
            $user = Auth::user();
            $userType = $user->user_type;
            $appointmentsQuery = Appointment::with(['patient.user', 'dentist.user', 'branch', 'schedule']);

            // Filter appointments based on user type
            if ($userType === 'Patient') {
                $appointmentsQuery->where('patient_id', $user->user_id);
            } elseif ($userType === 'Dentist') {
                $appointmentsQuery->where('dentist_id', $user->user_id);
            } elseif ($userType === 'Staff') {
                $staff = Staff::where('staff_id', $user->user_id)->first();
                if ($staff && $staff->branches->first()) {
                    $appointmentsQuery->where('appointments.branch_id', $staff->branches->first()->branch_id);
                } else {
                    $appointmentsQuery->whereRaw('1 = 0'); // No data if no branch assigned
                }
            } // Owner sees all appointments (no additional filter)

            $appointments = $appointmentsQuery->get();

            // Appointment statistics
            $scheduled = $appointments->where('status', 'Scheduled')->count();
            $completed = $appointments->where('status', 'Completed')->count();
            $cancelled = $appointments->where('status', 'Cancelled')->count();

            // Weekly overview for chart
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
            $weeklyAppointments = (clone $appointmentsQuery)
                ->join('schedules', 'appointments.schedule_id', '=', 'schedules.schedule_id')
                ->whereBetween('appointments.created_at', [$weekStart, $weekEnd])
                ->selectRaw('DATE(schedules.schedule_date) as date, 
                    SUM(CASE WHEN appointments.status = "Scheduled" THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN appointments.status = "Completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN appointments.status = "Cancelled" THEN 1 ELSE 0 END) as cancelled')
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            $overview = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $dateKey = $date->format('Y-m-d');
                $dateLabel = $date->format('j M');
                $data = $weeklyAppointments->get($dateKey);
                $overview[] = [
                    'date' => $dateLabel,
                    'completed' => $data ? (int) $data->completed : 0,
                    'cancelled' => $data ? (int) $data->cancelled : 0,
                    'scheduled' => $data ? (int) $data->scheduled : 0,
                ];
            }

            // Scheduled appointments for table
            $scheduledAppointments = $appointments->where('status', 'Scheduled')
                ->take(5)
                ->map(function ($appointment) {
                    $patientName = 'N/A';
                    $dentistName = 'N/A';
                    try {
                        if ($appointment->patient && $appointment->patient->user) {
                            $patientName = $appointment->patient->user->first_name . ' ' . $appointment->patient->user->last_name;
                        }
                        if ($appointment->dentist && $appointment->dentist->user) {
                            $dentistName = $appointment->dentist->user->first_name . ' ' . $appointment->dentist->user->last_name;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Invalid patient or dentist data for appointment ID ' . $appointment->id . ': ' . $e->getMessage());
                    }
                    return [
                        'patient_name' => $patientName,
                        'dentist_name' => $dentistName,
                        'schedule_date' => $appointment->schedule ? Carbon::parse($appointment->schedule->schedule_date)->format('F j, Y') : 'N/A',
                        'start_time' => $appointment->schedule ? Carbon::parse($appointment->schedule->start_time)->format('h:i A') : 'N/A',
                        'branch_name' => $appointment->branch ? $appointment->branch->branch_name : 'N/A',
                    ];
                })->values()->toArray(); // Ensure the result is an array

            Log::info('Scheduled Appointments:', ['data' => $scheduledAppointments]);

            return response()->json([
                'appointments' => [
                    'scheduled' => $scheduled,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'overview' => $overview,
                ],
                'scheduledAppointments' => $scheduledAppointments,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in dashboard data', [
                'user_type' => $user->user_type ?? 'N/A',
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'appointments' => [
                    'scheduled' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'overview' => [],
                ],
                'scheduledAppointments' => [],
            ], 500);
        }
    }
}