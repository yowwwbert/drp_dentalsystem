<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $perPage = request()->input('per_page', 10);
        $branches = Branches::select([
            'branch_id',
            'branch_name',
            'branch_address',
            'branch_contact',
            'branch_email',
            'branch_logo',
            'branch_image',
            'branch_map',
            'branch_facebook',
            'branch_instagram',
            'operating_days',
            'opening_time',
            'closing_time',
        ])
        ->paginate($perPage);

        return response()->json([
            'branches' => $branches->items(),
            'pagination' => [
                'currentPage' => $branches->currentPage(),
                'totalPages' => $branches->lastPage(),
                'perPage' => $branches->perPage(),
                'totalRecords' => $branches->total(),
            ],
        ]);
    }

    /**
     * Store a newly created branch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'required|string|max:255',
            'contactNumber' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'map' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'operatingDays' => 'nullable|json',
            'openingTime' => 'nullable|date_format:H:i',
            'closingTime' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for branch creation', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $branchData = $request->all();
        // Convert operatingDays array to JSON if it exists
        if (isset($branchData['operatingDays']) && is_array($branchData['operatingDays'])) {
            $branchData['operatingDays'] = json_encode($branchData['operatingDays']);
        }

        $branch = Branches::create($branchData);

        return response()->json([
            'message' => 'Branch created successfully',
            'branch' => [
                'branch_id' => $branch->branch_id,
                'branch_name' => $branch->branch_name,
                'branch_address' => $branch->branch_address,
                'branch_contact' => $branch->branch_contact,
                'branch_email' => $branch->branch_email,
                'branch_logo' => $branch->branch_logo,
                'branch_image' => $branch->branch_image,
                'branch_map' => $branch->branch_map,
                'branch_facebook' => $branch->branch_facebook,
                'branch_instagram' => $branch->branch_instagram,
                'operating_days' => $branch->operating_days,
                'opening_time' => $branch->opening_time,
                'closing_time' => $branch->closing_time,
            ]
        ], 201);
    }

    /**
     * Update the specified branch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $branch = Branches::where('branch_id', $id)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'branch_address' => 'required|string|max:255',
            'contactNumber' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255',
            'map' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'operatingDays' => 'nullable|json',
            'openingTime' => 'nullable|date_format:H:i',
            'closingTime' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for branch update', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $branchData = $request->all();
        // Convert operatingDays array to JSON if it exists
        if (isset($branchData['operatingDays']) && is_array($branchData['operatingDays'])) {
            $branchData['operatingDays'] = json_encode($branchData['operatingDays']);
        }

        $branch->update($branchData);

        return response()->json([
            'message' => 'Branch updated successfully',
            'branch' => [
                'branch_id' => $branch->branch_id,
                'branch_name' => $branch->branch_name,
                'branch_address' => $branch->branch_address,
                'branch_contact' => $branch->branch_contact,
                'branch_email' => $branch->branch_email,
                'branch_logo' => $branch->branch_logo,
                'branch_image' => $branch->branch_image,
                'branch_map' => $branch->branch_map,
                'branch_facebook' => $branch->branch_facebook,
                'branch_instagram' => $branch->branch_instagram,
                'operating_days' => $branch->operating_days,
                'opening_time' => $branch->opening_time,
                'closing_time' => $branch->closing_time,
            ]
        ]);
    }
}
