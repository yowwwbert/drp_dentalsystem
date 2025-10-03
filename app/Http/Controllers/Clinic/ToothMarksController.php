<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Patient\ToothMark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToothMarksController extends Controller
{
    public function index()
    {
        $toothMarks = ToothMark::with('createdBy')->get()->map(function ($mark) {
            return [
                'tooth_mark_id' => $mark->tooth_mark_id,
                'mark_name' => $mark->mark_name,
                'mark_color' => $mark->mark_color,
            ];
        });

        return response()->json(['toothMarks' => $toothMarks]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'mark_name' => 'required|string|max:255|unique:tooth_marks,mark_name',
            'mark_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        try {
            $toothMark = ToothMark::create([
                'tooth_mark_id' => Str::uuid()->toString(),
                'mark_name' => $validated['mark_name'],
                'mark_color' => $validated['mark_color'],
                'created_by' => $user->user_id,
            ]);

            return response()->json([
                'message' => 'Tooth mark created successfully',
                'tooth_mark_id' => $toothMark->tooth_mark_id,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create tooth mark: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create tooth mark'], 500);
        }
    }

    public function update(Request $request, $tooth_mark_id)
    {
        $user = Auth::user();
        $toothMark = ToothMark::where('tooth_mark_id', $tooth_mark_id)->firstOrFail();

        $validated = $request->validate([
            'mark_name' => 'required|string|max:255|unique:tooth_marks,mark_name,' . $tooth_mark_id . ',tooth_mark_id',
            'mark_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        try {
            $toothMark->update([
                'mark_name' => $validated['mark_name'],
                'mark_color' => $validated['mark_color'],
            ]);

            return response()->json([
                'message' => 'Tooth mark updated successfully',
                'tooth_mark_id' => $toothMark->tooth_mark_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tooth mark: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update tooth mark'], 500);
        }
    }
}