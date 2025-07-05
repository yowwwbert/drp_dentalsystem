<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TreatmentController extends Controller
{
    /**
     * Show the services page.
     */
    public function getTreatments(Request $request)
    {
        $treatment = Treatment::all(); // Fetch all services
        Log::info('Fetched services for clinic:', $treatment->toArray());
        return response()->json($treatment);
    }
}
