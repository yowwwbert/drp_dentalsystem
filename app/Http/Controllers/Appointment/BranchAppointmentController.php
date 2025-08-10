<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Branches;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BranchAppointmentController extends Controller
{
    public function getBranchesForAppointment(Request $request)
    {
        $branches = Branches::select('branch_id', 'branch_name', 'branch_address')
            ->get();
        Log::info('Fetched branches for appointment:', $branches->toArray());
        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        Log::info('Branch appointment stored:', $validated);

        session(['selected_branch_id' => $validated['branch_id']]);

        return redirect()->route('appointment.show.dentists');
    }
}