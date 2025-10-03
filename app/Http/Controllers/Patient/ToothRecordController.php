<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient\Teeth;
use App\Models\Patient\ToothSurface;
use App\Models\Patient\ToothMark;
use App\Models\Users\Patient;
use App\Models\Appointment\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToothRecordController extends Controller
{
    public function edit(Request $request, $tooth_id)
    {
        $user = Auth::user();

        // Fetch tooth with its surfaces and dental chart
        $tooth = Teeth::with(['surfaces', 'dentalChart'])
            ->where('tooth_id', $tooth_id)
            ->firstOrFail();

        // Check if dental chart exists
        if (!$tooth->dentalChart) {
            return response()->json(['error' => 'Dental chart not found for this tooth'], 404);
        }

        // Fetch patient for authorization
        $patient = Patient::where('patient_id', $tooth->dentalChart->patient_id)
            ->firstOrFail();

        // Authorization check
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

        // Fetch available tooth marks for surfaces
        $toothMarks = ToothMark::pluck('mark_name', 'tooth_mark_id')->toArray();

        return response()->json([
            'tooth' => [
                'tooth_id' => $tooth->tooth_id,
                'tooth_number' => $tooth->tooth_number,
                'tooth_notes' => $tooth->tooth_notes,
                'tooth_status' => $tooth->tooth_status, // tooth_mark_id
            ],
            'surfaces' => $tooth->surfaces->map(function ($surface) use ($toothMarks) {
                return [
                    'surface_id' => $surface->surface_id,
                    'surface_type' => $surface->surface_type,
                    'surface_status' => $surface->surface_status, // tooth_mark_id
                    'surface_notes' => $surface->surface_notes,
                    'mark_name' => $toothMarks[$surface->surface_status] ?? 'Unknown',
                ];
            })->toArray(),
            'toothMarks' => $toothMarks,
        ]);
    }

    public function update(Request $request, $tooth_id)
    {
        $user = Auth::user();

        // Validate request
        $validated = $request->validate([
            'tooth_notes' => 'nullable|string|max:1000',
            'tooth_status' => 'nullable|string|exists:tooth_marks,tooth_mark_id',
            'surfaces' => 'required|array',
            'surfaces.*.surface_id' => 'required|string|exists:tooth_surfaces,surface_id',
            'surfaces.*.surface_status' => 'nullable|string|exists:tooth_marks,tooth_mark_id',
            'surfaces.*.surface_notes' => 'nullable|string|max:1000',
        ]);

        $tooth = Teeth::where('tooth_id', $tooth_id)->firstOrFail();

        // Check if dental chart exists
        if (!$tooth->dentalChart) {
            return response()->json(['error' => 'Dental chart not found for this tooth'], 404);
        }

        $patient = Patient::where('patient_id', $tooth->dentalChart->patient_id)->firstOrFail();

        // Authorization check
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
            return DB::transaction(function () use ($user, $tooth, $validated) {
                // Update tooth
                $tooth->update([
                    'tooth_notes' => $validated['tooth_notes'],
                    'tooth_status' => $validated['tooth_status'],
                    'updated_by' => $user->user_id,
                ]);

                // Update surfaces
                foreach ($validated['surfaces'] as $surfaceData) {
                    $surface = ToothSurface::where('surface_id', $surfaceData['surface_id'])
                        ->where('tooth_id', $tooth->tooth_id)
                        ->firstOrFail();
                    $surface->update([
                        'surface_status' => $surfaceData['surface_status'],
                        'surface_notes' => $surfaceData['surface_notes'],
                        'updated_by' => $user->user_id,
                    ]);
                }

                return response()->json([
                    'message' => 'Tooth and surfaces updated successfully',
                    'tooth_id' => $tooth->tooth_id,
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update tooth record:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to update tooth record. Please try again later.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}