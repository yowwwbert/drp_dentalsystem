<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Users\Dentist;
use App\Models\Users\Staff;
use App\Models\Clinic\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffManagementController extends Controller
{
    /**
     * Assign a new branch to a staff member, preserving history in user_branch
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignBranch(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'branch_id' => 'required|exists:branches,branch_id',
                'user_type' => 'required|in:dentist,receptionist',
            ]);

            // Determine the model based on user_type
            $model = $request->user_type === 'dentist' ? Dentist::class : Staff::class;
            $user = $model::findOrFail($id);

            // Log request details
            Log::info('Assigning branch', [
                'user_id' => $user->getKey(),
                'branch_id' => $request->branch_id,
                'user_type' => $request->user_type,
            ]);

            // Start transaction
            DB::beginTransaction();

            // Check for existing assignment
            $existing = DB::table('user_branch')
                ->where('user_id', $user->getKey())
                ->where('branch_id', $request->branch_id)
                ->first();

            if ($existing) {
                // Update updated_at for existing assignment
                DB::table('user_branch')
                    ->where('user_id', $user->getKey())
                    ->where('branch_id', $request->branch_id)
                    ->update(['updated_at' => now()]);
                Log::info('Updated existing user_branch', [
                    'user_id' => $user->getKey(),
                    'branch_id' => $request->branch_id,
                    'updated_at' => now(),
                ]);
            } else {
                // For dentists: Remove old assignments to ensure single branch
                if ($request->user_type === 'dentist') {
                    $deleted = DB::table('user_branch')
                        ->where('user_id', $user->getKey())
                        ->delete();
                    Log::info('Deleted old user_branch records for dentist', [
                        'user_id' => $user->getKey(),
                        'deleted_count' => $deleted,
                    ]);
                }
                // Insert new record
                DB::table('user_branch')->insert([
                    'user_id' => $user->getKey(),
                    'branch_id' => $request->branch_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('Inserted new user_branch record', [
                    'user_id' => $user->getKey(),
                    'branch_id' => $request->branch_id,
                ]);
            }

            // Fetch the branch name for the response
            $branch = Branches::findOrFail($request->branch_id);

            // Verify database state
            $currentBranch = DB::table('user_branch')
                ->where('user_id', $user->getKey())
                ->orderBy('updated_at', 'desc')
                ->first();
            Log::info('Current user_branch state', [
                'user_id' => $user->getKey(),
                'current_branch_id' => $currentBranch ? $currentBranch->branch_id : null,
                'expected_branch_id' => $request->branch_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($request->user_type) . ' branch assigned successfully',
                'branch_name' => $branch->branch_name,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in assignBranch', [
                'id' => $id,
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('User or branch not found in assignBranch', [
                'id' => $id,
                'branch_id' => $request->branch_id,
                'user_type' => $request->user_type,
            ]);
            return response()->json([
                'success' => false,
                'message' => ucfirst($request->user_type) . ' or branch not found',
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error in assignBranch', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . ($e->getCode() == 23000 ? 'Duplicate branch assignment detected' : 'An error occurred'),
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning branch', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the branch',
            ], 500);
        }
    }

    /**
     * Get branch assignment history for a user
     *
     * @param string $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBranchHistory(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'user_type' => 'required|in:dentist,receptionist',
            ]);

            // Determine the model to verify user existence
            $model = $request->user_type === 'dentist' ? Dentist::class : Staff::class;
            $user = $model::findOrFail($id);

            // Fetch branch history from user_branch
            $history = DB::table('user_branch')
                ->where('user_id', $id)
                ->join('branches', 'user_branch.branch_id', '=', 'branches.branch_id')
                ->select('branches.branch_name', 'user_branch.created_at', 'user_branch.updated_at')
                ->orderBy('user_branch.updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'history' => $history->map(function ($entry) use ($id) {
                    $latest = DB::table('user_branch')
                        ->where('user_id', $id)
                        ->orderBy('updated_at', 'desc')
                        ->first();
                    return [
                        'branch_name' => $entry->branch_name,
                        'created_at' => $entry->created_at,
                        'updated_at' => $entry->updated_at,
                        'is_current' => $entry->updated_at === ($latest ? $latest->updated_at : null),
                    ];
                }),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed in getBranchHistory', [
                'id' => $id,
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('User not found in getBranchHistory', [
                'id' => $id,
                'user_type' => $request->user_type,
            ]);
            return response()->json([
                'success' => false,
                'message' => ucfirst($request->user_type) . ' not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching branch history', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'stack' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching branch history',
            ], 500);
        }
    }
}