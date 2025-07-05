<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use Illuminate\Http\Request;
use App\Models\Users\Dentist;
use App\Models\Pivot\UserBranch;
use Illuminate\Support\Facades\Log;

class DentistAppointmentController extends Controller
{
    public function getDentistsForBranch($branch_id)
    {
        // Fetch dentists for the given branch
        // Get all dentist IDs associated with the branch from the pivot table
        $dentist_id = UserBranch::where('branch_id', $branch_id)->pluck('user_id');

        // Fetch dentist details using the IDs
        $dentists = User::whereIn('user_id', $dentist_id)->get('first_name', 'last_name');

        Log::info('Fetched dentists for branch ' . $branch_id . ':', $dentists->toArray());
        return response()->json($dentists);
    }
}
