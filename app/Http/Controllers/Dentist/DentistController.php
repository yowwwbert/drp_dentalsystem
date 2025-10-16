<?php
namespace App\Http\Controllers\Dentist;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Branches;
use App\Models\Users\Dentist;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentistController extends Controller
{
    public function index($dentist_id = null)
    {
        return Inertia::render('Accounts/Owner Dashboard/Own_DentistInformation', [
            'dentistId' => $dentist_id,
        ]);
    }

   public function getDentists(Request $request)
{
    $user = auth()->user();
    $query = Dentist::with(['user', 'branches']);

    // Branch filtering logic
    if ($request->has('branch_id')) {
        Log::info('Branch filter applied: ' . $request->input('branch_id'));
        $branchId = $request->input('branch_id');
        
        // Only allow branch filtering if user is Owner OR the branch matches their own
        if ($user->role === 'Owner' || $user->user_branch === $branchId) {
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } else {
            // Staff trying to access another branch - deny
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    } else {
        // No branch_id provided
        if ($user->role !== 'Owner' && $user->user_branch) {
            // Staff members default to their own branch
            $query->whereHas('branches', function ($q) use ($user) {
                $q->where('branch_id', $user->user_branch);
            });
        }
        // Owners with no branch_id param see all dentists
    }

    // Other filters
    if ($request->has('dentist_type')) {
        $query->where('dentist_type', $request->input('dentist_type'));
    }
    
    if ($request->has('status')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('status', $request->input('status'));
        });
    }

    $dentists = $query->get()->map(function ($dentist) {
        return [
            'dentist_id' => $dentist->dentist_id,
            'dentist_type' => $dentist->dentist_type,
            'first_name' => $dentist->user->first_name,
            'last_name' => $dentist->user->last_name,
            'email_address' => $dentist->user->email_address,
            'phone_number' => $dentist->user->phone_number,
            'user_id' => $dentist->user->user_id, // Added for the dental chart
            'position' => 'Dentist',
            'status' => $dentist->user->status,
            'branch_name' => $dentist->branches->first()?->branch_name ?? 'N/A',
            'branch_id' => $dentist->branches->first()?->branch_id ?? null
        ];
    });

    return response()->json([
        'dentists' => $dentists,
        'branches' => Branches::select('branch_id', 'branch_name')->get(),
        'pagination' => [
            'currentPage' => 1,
            'totalPages' => 1,
            'perPage' => 10,
            'totalRecords' => $dentists->count()
        ]
    ]);
}
    public function updateDentist(Request $request, $dentistId)
    {
        $dentist = Dentist::with('user')->where('dentist_id', $dentistId)->firstOrFail();
        $user = $dentist->user;

        $validatedData = $request->validate([
            'email_address' => 'required|email',
            'phone_number' => 'required|string|max:15',
            'dentist_type' => 'required|in:Dentist,Head Dentist,Dental Assistant',
            'branch_name' => 'required|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $user->fill([
            'email_address' => $validatedData['email_address'],
            'phone_number' => $validatedData['phone_number'],
            'status' => $validatedData['status'],
        ])->save();

        $dentist->fill([
            'dentist_type' => $validatedData['dentist_type'],
        ])->save();

        $branch = Branches::where('branch_name', $validatedData['branch_name'])->firstOrFail();
        $dentist->branches()->sync([$branch->branch_id]);

        return response()->json(['success' => true, 'message' => 'Dentist updated successfully']);
    }

    public function getDentistDetail($id)
    {
        $dentist = Dentist::with(['user', 'branches'])
            ->where('dentist_id', $id)
            ->firstOrFail();

        $dentistData = [
            'dentist_id' => $dentist->dentist_id,
            'dentist_type' => $dentist->dentist_type,
            'first_name' => $dentist->user->first_name,
            'last_name' => $dentist->user->last_name,
            'email_address' => $dentist->user->email_address,
            'phone_number' => $dentist->user->phone_number,
            'position' => 'Dentist',
            'status' => $dentist->user->status,
            'branch_name' => $dentist->branches->first() ? $dentist->branches->first()->branch_name : 'N/A',
            'branch_id' => $dentist->branches->first() ? $dentist->branches->first()->branch_id : null
        ];

        return response()->json([
            'dentist' => $dentistData,
            'branches' => Branches::select('branch_id', 'branch_name')->get(),
        ]);
    }
}
