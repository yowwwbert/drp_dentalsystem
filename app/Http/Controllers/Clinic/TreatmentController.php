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
        $treatments = Treatment::select(
            'treatment_id',
            'treatment_name',
            'treatment_type',
            'treatment_duration',
            'treatment_description',
            'treatment_cost',
            'is_active',
        )->get();

        // Format the treatment_cost as a decimal with 2 places
        $treatments->transform(function ($item) {
            if (!is_null($item->treatment_cost)) {
            $item->treatment_cost = '₱ ' . number_format((float)$item->treatment_cost, 0, '.', ',');
            }
            return $item;
        });

        return response()->json($treatments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'treatment_name' => 'required|string|max:255',
            'treatment_duration' => 'required|integer|max:255',
            'treatment_description' => 'nullable|string',
            'treatment_cost' => 'nullable|string',
            'treatment_type' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        // Normalize treatment_cost by removing decimals
        if ($validated['treatment_cost'] !== null) {
            $validated['treatment_cost'] = preg_replace('/[^0-9]/', '', $validated['treatment_cost']);
        }

        $treatment = Treatment::create($validated);
        return response()->json($treatment, 201);
    }

    public function update(Request $request, $id)
    {
        $treatment = Treatment::findOrFail($id);
        $validated = $request->validate([
            'treatment_name' => 'required|string|max:255',
            'treatment_duration' => 'required|integer|max:255',
            'treatment_description' => 'nullable|string',
            'treatment_cost' => 'nullable|string',
            'treatment_type' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        // Normalize treatment_cost by removing decimals
        if ($validated['treatment_cost'] !== null) {
            $validated['treatment_cost'] = preg_replace('/[^0-9]/', '', $validated['treatment_cost']);
        }

        $treatment->update($validated);
        return response()->json($treatment);
    }
}
