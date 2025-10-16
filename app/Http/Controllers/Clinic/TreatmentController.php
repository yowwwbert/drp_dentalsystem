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
            'treatment_type', // Using treatment_type as the category-like field
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
            'treatment_type' => 'nullable|in:General,Restorative,Cosmetic,Surgical,Preventive,Orthodontic,Pediatric,Endodontic,Periodontic', // Updated to use treatment_type with ENUM values
            'is_active' => 'required|boolean'
        ]);

        // Normalize treatment_cost by removing non-numeric characters and convert to integer
        if ($validated['treatment_cost'] !== null) {
            $cleanCost = preg_replace('/[^0-9]/', '', $validated['treatment_cost']);
            $validated['treatment_cost'] = $cleanCost ? (int)$cleanCost : 0;
        } else {
            $validated['treatment_cost'] = 0;
        }

        $treatment = Treatment::create($validated);
        
        // Format the returned treatment_cost for frontend consistency
        $treatment->treatment_cost = '₱ ' . number_format((float)$treatment->treatment_cost, 0, '.', ',');
        
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
            'treatment_type' => 'nullable|in:General,Restorative,Cosmetic,Surgical,Preventive,Orthodontic,Pediatric,Endodontic,Periodontic', // Updated to use treatment_type with ENUM values
            'is_active' => 'required|boolean'
        ]);

        // Normalize treatment_cost by removing non-numeric characters and convert to integer
        if ($validated['treatment_cost'] !== null) {
            $cleanCost = preg_replace('/[^0-9]/', '', $validated['treatment_cost']);
            $validated['treatment_cost'] = $cleanCost ? (int)$cleanCost : 0;
        } else {
            $validated['treatment_cost'] = $treatment->treatment_cost; // Keep existing value
        }

        $treatment->update($validated);
        
        // Format the returned treatment_cost for frontend consistency
        $treatment->treatment_cost = '₱ ' . number_format((float)$treatment->treatment_cost, 0, '.', ',');
        
        return response()->json($treatment);
    }
}