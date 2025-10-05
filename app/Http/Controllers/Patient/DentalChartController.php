<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Users\Patient;
use App\Models\Patient\DentalChart;
use App\Models\Appointment\Appointment;
use App\Models\Patient\Teeth;
use App\Models\Patient\ToothSurface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DentalChartController extends Controller
{
    public function show(Request $request, $patient_id)
    {
        $user = Auth::user();

        $query = Patient::with(['dentalCharts' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->where('patient_id', $patient_id);

        if ($user->user_type === 'receptionist') {
            $query->where('branch_id', $user->user_branch);
        } elseif ($user->user_type === 'dentist') {
            $query->whereIn('patient_id', Appointment::where('dentist_id', $user->user_id)
                ->pluck('patient_id'));
        }

        $patient = $query->firstOrFail();

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
        ]);
    }

    public function getToothRecords(Request $request, $patient_id)
    {
        $user = Auth::user();

        // Validate user access to the patient
        $patientQuery = Patient::where('patient_id', $patient_id);
        if ($user->user_type === 'receptionist') {
            $patientQuery->where('branch_id', $user->user_branch);
        } elseif ($user->user_type === 'dentist') {
            $patientQuery->whereIn('patient_id', Appointment::where('dentist_id', $user->user_id)
                ->pluck('patient_id'));
        }

        $patient = $patientQuery->first();
        if (!$patient) {
            return response()->json(['error' => 'Patient not found or unauthorized'], 403);
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
                    'surfaces.toothMark' // Include toothMark for surfaces
                ])
                ->get();

            $toothRecords = $teeth->map(function ($tooth) {
                $latestTreatment = $tooth->treatments->first();
                $surfaces = $tooth->surfaces->map(function ($surface) {
                    return [
                        'surface_id' => $surface->surface_id,
                        'surface_type' => $surface->surface_type,
                        'surface_status' => $surface->surface_status,
                        'surface_notes' => $surface->surface_notes,
                        'mark_name' => $surface->toothMark ? $surface->toothMark->mark_name : 'Healthy',
                        'mark_color' => $surface->toothMark ? $surface->toothMark->mark_color : '#00FF00',
                    ];
                })->toArray();

                return [
                    'tooth_id' => $tooth->tooth_id,
                    'tooth_number' => $tooth->tooth_number,
                    'tooth_status' => $tooth->tooth_status,
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
}