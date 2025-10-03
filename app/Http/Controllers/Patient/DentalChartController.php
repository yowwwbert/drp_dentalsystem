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
                        'surface_status' => $surface->surface_status, // Return tooth_mark_id
                        'surface_notes' => $surface->surface_notes,
                        'mark_name' => $surface->toothMark ? $surface->toothMark->mark_name : 'Healthy',
                        'mark_color' => $surface->toothMark ? $surface->toothMark->mark_color : '#00FF00', // Default to green for Healthy
                    ];
                })->toArray();

                return [
                    'tooth_id' => $tooth->tooth_id,
                    'tooth_number' => $tooth->tooth_number,
                    'tooth_status' => $tooth->tooth_status, // Return tooth_mark_id
                    'condition' => $tooth->toothMark ? $tooth->toothMark->mark_name : 'Healthy',
                    'mark_color' => $tooth->toothMark ? $tooth->toothMark->mark_color : '#00FF00', // Include mark_color
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

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'patient_id' => 'required|string|exists:patients,patient_id',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        if ($user->user_type === 'receptionist' && $patient->branch_id !== $user->user_branch) {
            return response()->json(['error' => 'Unauthorized: Patient not in your branch'], 403);
        } elseif ($user->user_type === 'dentist') {
            $hasAppointment = Appointment::where('patient_id', $patient->patient_id)
                ->where('dentist_id', $user->user_id)
                ->exists();
            if (!$hasAppointment) {
                return response()->json(['error' => 'Unauthorized: No appointment with this patient'], 403);
            }
        }

        try {
            return DB::transaction(function () use ($user, $validated) {
                $chart = DentalChart::create([
                    'chart_id' => \Str::uuid()->toString(),
                    'patient_id' => $validated['patient_id'],
                ]);
                Log::debug('Created dental chart:', $chart->toArray());

                $toothNumbers = array_merge(
                    range(11, 18), // Upper right
                    range(21, 28), // Upper left
                    range(31, 38), // Lower left
                    range(41, 48)  // Lower right
                );

                $surfaces = ['Mesial', 'Distal', 'Buccal', 'Lingual', 'Occlusal'];
                $createdTeeth = [];
                $createdSurfaces = [];

                foreach ($toothNumbers as $number) {
                    $tooth = Teeth::create([
                        'tooth_id' => \Str::uuid()->toString(),
                        'chart_id' => $chart->chart_id,
                        'tooth_number' => (string)$number,
                        'tooth_notes' => null,
                        'tooth_status' => null,
                        'created_by' => $user->user_id,
                        'updated_by' => $user->user_id,
                    ]);
                    Log::debug('Created tooth:', $tooth->toArray());
                    $createdTeeth[] = $tooth->tooth_id;

                    foreach ($surfaces as $surfaceType) {
                        $surface = ToothSurface::create([
                            'surface_id' => \Str::uuid()->toString(),
                            'tooth_id' => $tooth->tooth_id,
                            'surface_type' => $surfaceType,
                            'surface_status' => null,
                            'created_by' => $user->user_id,
                            'updated_by' => $user->user_id,
                            'surface_notes' => null,
                        ]);
                        Log::debug('Created surface:', $surface->toArray());
                        $createdSurfaces[] = $surface->surface_id;
                    }
                }

                return response()->json([
                    'message' => 'Dental chart created successfully with ' . count($createdTeeth) . ' teeth and ' . count($createdSurfaces) . ' surfaces',
                    'chart' => [
                        'chart_id' => $chart->chart_id,
                        'patient_id' => $chart->patient_id,
                        'created_at' => $chart->created_at,
                    ],
                ], 201);
            }, 5);
        } catch (\Exception $e) {
            Log::error('Failed to create dental chart, teeth, or surfaces:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to create dental chart. Please try again later.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function withoutCharts(Request $request)
    {
        $user = Auth::user();
        $query = Patient::query();

        if ($user->user_type === 'receptionist') {
            $query->where('branch_id', $user->user_branch);
        } elseif ($user->user_type === 'dentist') {
            $query->whereIn('patient_id', Appointment::where('dentist_id', $user->user_id)
                ->pluck('patient_id'));
        }

        $patients = $query->whereDoesntHave('dentalCharts')->get();

        return response()->json([
            'patients' => $patients->map(function ($patient) {
                return [
                    'patient_id' => $patient->patient_id,
                    'name' => $patient->first_name . ' ' . $patient->last_name,
                ];
            }),
        ]);
    }
}