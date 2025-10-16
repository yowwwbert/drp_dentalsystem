<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Users\Patient;
use App\Models\Patient\DentalChart;
use App\Models\Appointment\Appointment;
use App\Models\Patient\Teeth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DentalChartController extends Controller
{
    /**
     * Show dental chart (handles both staff POST and patient GET)
     */
    public function show(Request $request)
{
    $user = Auth::user();
    
    // Handle POST request (initial navigation from staff)
    if ($request->isMethod('post')) {
        $validated = $request->validate([
            'patient_id' => 'required|string',
        ]);
        
        // Store patient_id in session for this staff member
        session(['viewing_patient_id' => $validated['patient_id']]);
        
        $patient_id = $validated['patient_id'];
    } 
    // Handle GET request (reload or initial patient access)
    else {
        if ($user->user_type === 'Patient') {
            // Patients always see their own record
            $patient_id = $user->user_id;
        } else {
            // Staff reloading page - get from session
            $patient_id = session('viewing_patient_id');
            
            if (!$patient_id) {
                return redirect()->route('patients.index')
                    ->with('error', 'Please select a patient to view their dental chart.');
            }
        }
    }
    
    $patient = $this->getAuthorizedPatient($patient_id);

    $patient->load(['dentalCharts' => function ($query) {
        $query->orderBy('created_at', 'desc');
    }]);

    return Inertia::render('Dashboard/General/DentalChart', [
        'patient' => [
            'patient_id' => $patient->patient_id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'charts' => $patient->dentalCharts->map(function ($chart) {
                return [
                    'chart_id' => $chart->chart_id,
                    'created_at' => $chart->created_at,
                    'updated_at' => $chart->updated_at,
                ];
            }),
        ],
        'user' => Auth::user(),
    ]);
}

    public function getToothRecords(Request $request)
{
    $patient_id = $this->getPatientId($request);
    $patient = $this->getAuthorizedPatient($patient_id);

    if (!$patient) {
        return response()->json(['error' => 'Patient not found or unauthorized'], 403);
    }

    // Create chart if none exists
    if (!DentalChart::where('patient_id', $patient_id)->exists()) {
        try {
            Patient::createDentalChartForPatient($patient);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create dental chart: ' . $e->getMessage()], 500);
        }
    }

    // Get the latest dental chart
    $chart = DentalChart::where('patient_id', $patient_id)
        ->orderBy('created_at', 'desc')
        ->first();

    $toothRecords = [];
    if ($chart) {
        $teeth = Teeth::where('chart_id', $chart->chart_id)
            ->with([
                'toothMark',
                'treatments' => function ($query) {
                    $query->orderBy('treatment_date', 'desc')->take(1);
                },
                'surfaces.toothMark'
            ])
            ->get();

        $toothRecords = $teeth->map(function ($tooth) {
            $latestTreatment = $tooth->treatments->first();
            $surfaces = $tooth->surfaces->map(function ($surface) {
                return [
                    'surface_id' => $surface->surface_id,
                    'surface_type' => $surface->surface_type,
                    'surface_status' => $surface->surface_status,
                    'surface_status_type' => $surface->surface_status_type,
                    'diagnosed_by' => $surface->diagnosed_by,
                    'surface_notes' => $surface->surface_notes,
                    'updated_by' => $surface->updated_by,
                    'mark_name' => $surface->toothMark ? $surface->toothMark->mark_name : 'Healthy',
                    'mark_color' => $surface->toothMark ? $surface->toothMark->mark_color : '#00FF00',
                ];
            })->toArray();

            return [
                'tooth_id' => $tooth->tooth_id,
                'tooth_number' => $tooth->tooth_number,
                'tooth_status' => $tooth->tooth_status,
                'status_type' => $tooth->status_type,
                'diagnosed_by' => $tooth->diagnosed_by,
                'updated_by' => $tooth->updated_by,
                'condition' => $tooth->toothMark ? $tooth->toothMark->mark_name : 'Healthy',
                'mark_color' => $tooth->toothMark ? $tooth->toothMark->mark_color : '#00FF00',
                'last_treatment' => $latestTreatment ? $latestTreatment->toothMark->mark_name : 'None',
                'last_updated' => $latestTreatment ? $latestTreatment->treatment_date : $tooth->updated_at,
                'notes' => $tooth->tooth_notes ?? ($latestTreatment ? $latestTreatment->treatment_notes : null),
                'surfaces' => $surfaces,
            ];
        })->toArray();
    }

    return response()->json([
        'toothRecords' => $toothRecords,
    ], 200);
}

    /**
     * Get patient ID from request (query param or infer from user)
     */
    private function getPatientId(Request $request)
    {
        $user = Auth::user();

        // If patient_id is provided in request (from staff or API calls)
        if ($request->has('patient_id')) {
            return $request->patient_id;
        }

        // If no patient_id provided, and user is a patient, use their ID
        if ($user->user_type === 'Patient') {
            $patient = Patient::where('patient_id', $user->user_id)->first();
            if (!$patient) {
                Log::error('Patient record not found for user_id: ' . $user->user_id);
                abort(404, 'Patient record not found');
            }
            return $patient->patient_id;
        }

        abort(400, 'Patient ID is required');
    }

    /**
     * Get patient with authorization checks
     */
    private function getAuthorizedPatient($patient_id)
    {
        $user = Auth::user();
        $query = Patient::where('patient_id', $patient_id);

        // Apply role-based restrictions
        if ($user->user_type === 'Receptionist') {
            $query->where('branch_id', $user->user_branch);
        } elseif ($user->user_type === 'Dentist') {
            $query->whereIn('patient_id', Appointment::where('dentist_id', $user->user_id)
                ->pluck('patient_id'));
        } elseif ($user->user_type === 'Patient') {
            // Patients can only access their own records
            if ($patient_id !== $user->user_id) {
                abort(403, 'Unauthorized access');
            }
        }

        return $query->firstOrFail();
    }
}