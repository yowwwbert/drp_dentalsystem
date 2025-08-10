<?php
namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TreatmentController extends Controller
{
    public function getTreatments(Request $request)
    {
        $treatments = Treatment::select('treatment_id', 'treatment_name')->get();
        return response()->json($treatments);
    }
}
