<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Branches;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BranchAppointmentController extends Controller
{
    public function getBranchesForAppointment(Request $request)
    {
        $branches = Branches::select('branch_id', 'branch_name', 'branch_address') // Assuming you have an is_active column to filter active branches
            ->get();
        Log::info('Fetched branches for appointment:', $branches->toArray());
        return response()->json($branches);
    }

    public function store(Request $request)
    {
        // Validate the request to ensure branch_id and date_time are provided
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        // You can now use $validated['branch_id'] and $validated['date_time']
        // For demonstration, just return them in the response
        return redirect()->route('appointment.dentists', [
            'branch_id' => $validated['branch_id'],
        ]);
    }
}
