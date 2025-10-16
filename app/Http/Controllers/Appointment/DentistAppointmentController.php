<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Models\Pivot\UserBranch;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DentistAppointmentController extends Controller
{
    public function getDentistsForBranch($branch_id)
{
    $dentist_ids = UserBranch::where('branch_id', $branch_id)->pluck('user_id')->toArray();
    $dentists = User::whereIn('user_id', $dentist_ids)
        ->where('user_type', 'dentist') // Filter for dentists only
        ->select('user_id', 'first_name', 'last_name', 'profile_picture')
        ->get();
    return response()->json($dentists);
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,branch_id',
            'branch_name' => 'sometimes|string',
            'branch_address' => 'sometimes|string',
            'dentist_id' => 'required|exists:users,user_id',
            'dentist_name' => 'sometimes|string',
            'treatment_ids' => 'required|array|min:1|max:2',
            'treatment_ids.*' => 'exists:treatments,treatment_id',
            'treatment_names' => 'sometimes|array|min:1|max:2', // Added validation
            'treatment_names.*' => 'string', // Validate each treatment name
        ]);

        // Ensure treatment_names match treatment_ids
        if (isset($validated['treatment_ids']) && isset($validated['treatment_names'])) {
            if (count($validated['treatment_ids']) !== count($validated['treatment_names'])) {
                return response()->json(['error' => 'Number of treatment names must match number of treatment IDs'], 422);
            }
        }

        Log::info('Initial Appointment stored:', $validated);

        session([
            'selected_branch_id' => $validated['branch_id'],
            'selected_branch_name' => $validated['branch_name'] ?? null,
            'selected_branch_address' => $validated['branch_address'] ?? null,
            'selected_dentist_id' => $validated['dentist_id'],
            'selected_dentist_name' => $validated['dentist_name'] ?? null,
            'selected_treatment_ids' => $validated['treatment_ids'],
            'selected_treatment_names' => $validated['treatment_names'] ?? [], // Store treatment names
        ]);

        Log::info('Session after storing dentist and treatments:', [
            'selected_branch_id' => session('selected_branch_id'),
            'selected_branch_name' => session('selected_branch_name'),
            'selected_branch_address' => session('selected_branch_address'),
            'selected_dentist_id' => session('selected_dentist_id'),
            'selected_dentist_name' => session('selected_dentist_name'),
            'selected_treatment_ids' => session('selected_treatment_ids'),
            'selected_treatment_names' => session('selected_treatment_names'),
        ]);
        
        return redirect()->route('appointment.show.date-time');
    }
}