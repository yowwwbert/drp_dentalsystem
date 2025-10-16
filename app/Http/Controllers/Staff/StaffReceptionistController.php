<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Users\Staff;
use App\Models\Users\User;
use App\Models\Clinic\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffReceptionistController extends Controller
{
    /**
     * Display staff records page
     */
    public function index(Request $request)
    {
        return Inertia::render('records/StaffRecords');
    }

    /**
     * Fetch staff records with optional branch filter
     */
    public function getStaff(Request $request)
    {
        try {
            $query = Staff::with(['user', 'branches']);

            // Filter by branch if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $query->whereHas('branches', function ($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            $staff = $query->get()->map(function ($staffMember) {
                return [
                    'staff_id' => $staffMember->staff_id,
                    'first_name' => $staffMember->user->first_name ?? 'N/A',
                    'last_name' => $staffMember->user->last_name ?? 'N/A',
                    'email_address' => $staffMember->user->email_address ?? 'N/A',
                    'phone_number' => $staffMember->user->phone_number ?? 'N/A',
                    'position' => $staffMember->position,
                    'branches' => $staffMember->branches->map(function ($branch) {
                        return [
                            'branch_id' => $branch->branch_id,
                            'branch_name' => $branch->branch_name,
                        ];
                    }),
                    'created_at' => $staffMember->created_at,
                    'updated_at' => $staffMember->updated_at,
                ];
            });

            // Fetch all branches for filter dropdown
            $branches = Branches::select('branch_id', 'branch_name')->get();

            return response()->json([
                'staff' => $staff,
                'branches' => $branches,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch staff records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new staff member
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email_address' => 'required|email|unique:users,email_address',
            'phoneNumber' => 'required|string|max:20',
            'position' => 'required|string|in:Receptionist,Dental Assistant,Office Staff',
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        DB::beginTransaction();
        try {
            // Create user record
            $userId = 'USR-' . strtoupper(Str::random(8));
            
            $user = User::create([
                'user_id' => $userId,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email_address' => $validated['email_address'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make('password123'), // Default password
                'user_type' => 'Staff',
            ]);

            // Create staff record
            $staff = Staff::create([
                'staff_id' => $userId,
                'position' => $validated['position'],
            ]);

            // Attach branch
            $staff->branches()->attach($validated['branch_id']);

            DB::commit();

            return response()->json([
                'message' => 'Staff member created successfully',
                'staff' => $staff
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create staff member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing staff member
     */
    public function update(Request $request, $staffId)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email_address' => 'required|email|unique:users,email,' . $staffId . ',user_id',
            'phoneNumber' => 'required|string|max:20',
            'position' => 'required|string|in:Receptionist' ,
            'branch_id' => 'required|exists:branches,branch_id',
        ]);

        DB::beginTransaction();
        try {
            $staff = Staff::findOrFail($staffId);
            $user = $staff->user;

            // Update user record
            $user->update([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'email_address' => $validated['email_address'],
                'phone_number' => $validated['phoneNumber'],
            ]);

            // Update staff record
            $staff->update([
                'position' => $validated['position'],
            ]);

            // Sync branch (replace existing with new)
            $staff->branches()->sync([$validated['branch_id']]);

            DB::commit();

            return response()->json([
                'message' => 'Staff member updated successfully',
                'staff' => $staff
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update staff member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a staff member
     */
    public function destroy($staffId)
    {
        DB::beginTransaction();
        try {
            $staff = Staff::findOrFail($staffId);
            
            // Detach all branches
            $staff->branches()->detach();
            
            // Delete staff record
            $staff->delete();
            
            // Optionally delete user record
            // $staff->user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Staff member deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete staff member',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}