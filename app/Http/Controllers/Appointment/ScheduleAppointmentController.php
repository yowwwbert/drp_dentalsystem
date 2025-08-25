<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Pivot\DentistSchedule;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\AppointmentTreatment;
use App\Models\Clinic\Schedule; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ScheduleAppointmentController extends Controller
{

    public function getDentistSchedule($branch_id, Request $request)
    {
        $dentistId = $request->input('dentist_id');

        // Validate required parameters
        if (!$dentistId || !$branch_id) {
            return response()->json(['error' => 'Dentist ID and Branch ID are required'], 400);
        }

        // Fetch schedules for the dentist at the specified branch, only if active and available
        $schedule = DentistSchedule::where('dentist_id', $dentistId)
            ->join('schedules', 'dentist_schedule.schedule_id', '=', 'schedules.schedule_id')
            ->where('schedules.branch_id', $branch_id)
            ->where('schedules.is_active', true)
            ->select(
                'dentist_schedule.dentist_id',
                'dentist_schedule.schedule_id',
                'schedules.schedule_date',
                'schedules.start_time',
                'schedules.end_time'
            )
            ->orderBy('schedules.schedule_date')
            ->get();

        if ($schedule->isEmpty()) {
            return response()->json(['error' => 'No available schedules found for the specified dentist and branch'], 404);
        }

        return response()->json($schedule);
    }

    
}