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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
                'tooth_status' => $tooth->tooth_status,
                'status_type' => $tooth->status_type,
                'diagnosed_by' => $tooth->diagnosed_by,
            ],
            'surfaces' => $tooth->surfaces->map(function ($surface) use ($toothMarks) {
                return [
                    'surface_id' => $surface->surface_id,
                    'surface_type' => $surface->surface_type,
                    'surface_status' => $surface->surface_status,
                    'surface_notes' => $surface->surface_notes,
                    'status_type' => $surface->status_type,
                    'diagnosed_by' => $surface->diagnosed_by,
                    'mark_name' => $surface->surface_status ? ($toothMarks[$surface->surface_status] ?? 'Unknown') : 'Not Assessed',
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
            'status_type' => 'nullable|in:treated_here,pre_existing,observed',
            'diagnosed_by' => 'nullable|string|exists:users,user_id',
            'surfaces' => 'nullable|array',
            'surfaces.*.surface_id' => 'required|string|exists:tooth_surfaces,surface_id',
            'surfaces.*.surface_status' => 'nullable|string|exists:tooth_marks,tooth_mark_id',
            'surfaces.*.surface_notes' => 'nullable|string|max:1000',
            'surfaces.*.status_type' => 'nullable|in:treated_here,pre_existing,observed',
            'surfaces.*.diagnosed_by' => 'nullable|string|exists:users,user_id',
        ]);

        // Additional validation for tooth diagnosed_by when status_type is treated_here
        if ($validated['status_type'] === 'treated_here' && $user->user_type === 'dentist' && empty($validated['diagnosed_by'])) {
            return response()->json([
                'error' => 'Diagnosed by field is required for treatments performed here.',
            ], 422);
        }

        // Validate surfaces
        if (!empty($validated['surfaces'])) {
            foreach ($validated['surfaces'] as $index => $surfaceData) {
                // Fetch original surface to check if surface_status has changed
                $originalSurface = ToothSurface::where('surface_id', $surfaceData['surface_id'])->firstOrFail();
                $statusChanged = $surfaceData['surface_status'] !== $originalSurface->surface_status;

                // If surface_status has changed, status_type is required and must be valid
                if ($statusChanged && !isset($surfaceData['status_type'])) {
                    return response()->json([
                        'error' => "Status type is required for surface {$surfaceData['surface_id']} when surface status is updated.",
                    ], 422);
                }

                // If status_type is treated_here and user is dentist, diagnosed_by is required
                if (($surfaceData['status_type'] ?? null) === 'treated_here' && $user->user_type === 'dentist' && empty($surfaceData['diagnosed_by'])) {
                    return response()->json([
                        'error' => "Diagnosed by is required for surface {$surfaceData['surface_id']} when status_type is treated_here.",
                    ], 422);
                }
            }
        }

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
                // Log data before updating
                Log::info('Preparing to update tooth record', [
                    'tooth_id' => $tooth->tooth_id,
                    'tooth_number' => $tooth->tooth_number,
                    'current_state' => [
                        'tooth_notes' => $tooth->tooth_notes,
                        'tooth_status' => $tooth->tooth_status,
                        'status_type' => $tooth->status_type,
                        'diagnosed_by' => $tooth->diagnosed_by,
                        'updated_by' => $tooth->updated_by,
                    ],
                    'validated_data' => $validated,
                    'user' => [
                        'user_id' => $user->user_id,
                        'user_type' => $user->user_type,
                    ],
                ]);

                // Determine diagnosed_by value for tooth
                $diagnosedBy = $validated['status_type'] === 'treated_here' ? ($validated['diagnosed_by'] ?? null) : null;

                // Log diagnosed_by value before update
                Log::info('Tooth diagnosed_by value to be set', [
                    'tooth_id' => $tooth->tooth_id,
                    'diagnosed_by' => $diagnosedBy,
                    'status_type' => $validated['status_type'],
                ]);

                // Update tooth
                $updated = $tooth->update([
                    'tooth_notes' => $validated['tooth_notes'] ?? '',
                    'tooth_status' => $validated['tooth_status'],
                    'status_type' => $validated['status_type'],
                    'diagnosed_by' => $diagnosedBy,
                    'updated_by' => $user->user_id,
                ]);

                // Log update result
                Log::info('Tooth update result', [
                    'tooth_id' => $tooth->tooth_id,
                    'updated' => $updated,
                    'new_state' => [
                        'tooth_notes' => $tooth->tooth_notes,
                        'tooth_status' => $tooth->tooth_status,
                        'status_type' => $tooth->status_type,
                        'diagnosed_by' => $tooth->diagnosed_by,
                        'updated_by' => $tooth->updated_by,
                    ],
                ]);

                // Update surfaces
                if (!empty($validated['surfaces'])) {
                    foreach ($validated['surfaces'] as $surfaceData) {
                        $surface = ToothSurface::where('surface_id', $surfaceData['surface_id'])
                            ->where('tooth_id', $tooth->tooth_id)
                            ->firstOrFail();

                        // Set default status_type if not provided
                        $surfaceStatusType = $surfaceData['status_type'] ?? 'observed';

                        // Determine diagnosed_by value for surface
                        $surfaceDiagnosedBy = $surfaceStatusType === 'treated_here' ? ($surfaceData['diagnosed_by'] ?? null) : null;

                        // Log surface data before update
                        Log::info('Preparing to update tooth surface', [
                            'surface_id' => $surfaceData['surface_id'],
                            'tooth_id' => $tooth->tooth_id,
                            'current_state' => [
                                'surface_status' => $surface->surface_status,
                                'surface_notes' => $surface->surface_notes,
                                'status_type' => $surface->status_type,
                                'diagnosed_by' => $surface->diagnosed_by,
                            ],
                            'new_data' => [
                                'surface_status' => $surfaceData['surface_status'],
                                'surface_notes' => $surfaceData['surface_notes'] ?? '',
                                'status_type' => $surfaceStatusType,
                                'diagnosed_by' => $surfaceDiagnosedBy,
                            ],
                        ]);

                        $surfaceUpdated = $surface->update([
                            'surface_status' => $surfaceData['surface_status'],
                            'surface_notes' => $surfaceData['surface_notes'] ?? '',
                            'status_type' => $surfaceStatusType,
                            'diagnosed_by' => $surfaceDiagnosedBy,
                            'updated_by' => $user->user_id,
                        ]);

                        // Log surface update result
                        Log::info('Tooth surface update result', [
                            'surface_id' => $surface->surface_id,
                            'updated' => $surfaceUpdated,
                            'new_state' => [
                                'surface_status' => $surface->surface_status,
                                'surface_notes' => $surface->surface_notes,
                                'status_type' => $surface->status_type,
                                'diagnosed_by' => $surface->diagnosed_by,
                                'updated_by' => $surface->updated_by,
                            ],
                        ]);
                    }
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
