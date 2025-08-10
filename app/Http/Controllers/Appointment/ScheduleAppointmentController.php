<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Pivot\DentistSchedule;
use App\Models\Pivot\UserBranch;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\AppointmentTreatment;

use Illuminate\Http\Request;

class ScheduleAppointmentController extends Controller
{
    public function getDentistSchedule($branch_id, Request $request)
    {
        $dentistId = $request->input('dentist_id');
        
        // Validate required parameters
        if (!$dentistId || !$branch_id) {
            return response()->json(['error' => 'Dentist ID and Branch ID are required'], 400);
        }

        // Fetch schedules for the dentist at the specified branch, only if active
        $schedule = DentistSchedule::where('dentist_id', $dentistId)
            ->join('schedules', 'dentist_schedule.schedule_id', '=', 'schedules.schedule_id') // Corrected table alias
            ->where('schedules.branch_id', $branch_id)
            ->where('schedules.is_active', true) // Add check for active schedules
            ->select('dentist_schedule.dentist_id', 'dentist_schedule.schedule_id', 'schedules.schedule_date', 'schedules.start_time', 'schedules.end_time')
            ->orderBy('schedules.schedule_date')
            ->get();

        if ($schedule->isEmpty()) {
            return response()->json(['error' => 'No schedules found for the specified dentist and branch'], 404);
        }

        return response()->json($schedule);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
            'dentist_id' => 'required|exists:users,user_id',
            'schedule_id' => 'required|exists:schedules,schedule_id',
            'treatment_id' => 'required|exists:treatments,treatment_id',
        ]);

        if (!Auth::check()) {
            $request->session()->put('pending_appointment', $validated);
            return redirect()->route('login')->with('message', 'Please log in or sign up to book your appointment.');
        }

        if (Auth::user()->user_type !== 'Patient') {
            return redirect()->route('appointment')->with('error', 'Only patients can book appointments.');
        }

        $appointment = new Appointment();
        $appointment->patient_id = Auth::id();
        $appointment->dentist_id = $validated['dentist_id'];
        $appointment->schedule_id = $validated['schedule_id'];
        $appointment->branch_id = $validated['branch_id'];
        $appointment->status = 'Scheduled';
        
        $appointment->status_changed_by = Auth::id();
         $appointment->appointment_created_by = Auth::id();
        
        $appointment->save();

        // Attach treatment_id to the appointment_treatments pivot table
        $treatment = new AppointmentTreatment();
            $treatment->appointment_id = $appointment->appointment_id;
            $treatment->treatment_id = $validated['treatment_id'];
            $treatment->save();

        $request->session()->forget(['pending_appointment', 'selected_branch_id', 'selected_dentist_id', 'selected_treatment_id']);
        return redirect()->route('appointment.success')->with('message', 'Appointment booked successfully!');
    }
}