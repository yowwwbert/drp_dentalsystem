<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Branches;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class BranchAppointmentController extends Controller
{
    public function getBranches(Request $request)
    {
        $branches = Branches::orderBy('branch_name', 'asc')->get();
        Log::info('Fetched branches for appointment:', $branches->toArray());
        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
            'branch_name' => 'sometimes|string',
            'branch_address' => 'sometimes|string',
        ]);

        Log::info('Branch appointment stored:', $validated);

        session(['selected_branch_id' => $validated['branch_id']]);
        session(['selected_branch_name' => $validated['branch_name'] ?? null]);

        Log::info('Session after storing branch:', [
            'selected_branch_id' => session('selected_branch_id'),
            'selected_branch_name' => session('selected_branch_name'),
        ]);

        return redirect()->route('appointment.show.dentists');
    }
}