<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Models\Pivot\UserBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DentistAppointmentController extends Controller
{
    public function getDentistsForBranch($branch_id)
    {
        $dentist_ids = UserBranch::where('branch_id', $branch_id)->pluck('user_id')->toArray();
        $dentists = User::whereIn('user_id', $dentist_ids)
            ->select('user_id', 'first_name', 'last_name')
            ->get();
        return response()->json($dentists);
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'branch_id' => 'required|exists:branches,branch_id',
        'dentist_id' => 'required|exists:users,user_id',
        'treatment_ids' => 'required|array|min:1|max:2',
        'treatment_ids.*' => 'exists:treatments,treatment_id',
    ]);

    Log::info('Initial Appointment stored:', $validated);

    session([
        'selected_branch_id' => $validated['branch_id'],
        'selected_dentist_id' => $validated['dentist_id'],
        'selected_treatment_ids' => $validated['treatment_ids'], // ✅ array in session
    ]);

    return redirect()->route('appointment.show.date-time');
}


}