<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Models\Users\Patient;
use App\Models\Users\Dentist;
use App\Models\Users\Staff;
use App\Models\Users\Owner;
use App\Models\Clinic\Branches;
use App\Models\Clinic\Schedule;
use App\Models\PatientDetails\Guardian;
use App\Models\Pivot\UserBranch;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        // Pass the user_type query parameter to the Register.vue component
        $userType = $request->query('user_type', 'Patient');
        return Inertia::render('auth/Register', [
            'user_type' => $userType,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Prevent authenticated users from accessing this route
        if (Auth::check()) {
            Log::warning('Authenticated user attempted to access register route', [
                'user_id' => Auth::id(),
                'user_type' => Auth::user()->user_type,
            ]);
            return redirect()->route('dashboard')->with('error', 'You are already logged in.');
        }

        Log::info('Registration request data:', [
            'all' => $request->all(),
            'files' => $request->hasFile('valid_id') ? 'valid_id present' : 'valid_id not present',
            'user_type' => $request->user_type,
        ]);

        $request->validate([
            'first_name' => 'required|string|min:2|max:70',
            'last_name' => 'required|string|min:2|max:50',
            'middle_name' => 'nullable|string|min:2|max:50',
            'suffix' => 'nullable|in:Jr.,Sr.,II,III,IV,V',
            'age' => 'required|integer|min:1|max:120',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'sex' => 'required|in:Male,Female,Other',
            'occupation' => 'nullable|string|min:2|max:100',
            'email_address' => 'nullable|string|email|max:255|unique:users,email_address',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'user_type' => 'required|ble|in:Patient', // Restrict to Patient for this route
            'status' => 'required|in:Active,Inactive',
            'valid_id' => [
                'required_if:user_type,Patient',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'guardian_first_name' => 'nullable|string|max:70',
            'guardian_last_name' => 'nullable|string|max:70',
            'guardian_relationship' => 'nullable|string|max:50',
            'guardian_phone_number' => 'nullable|string|max:20',
            'guardian_email_address' => 'nullable|string|email|max:255',
            'guardian_valid_id' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
                function ($attribute, $value, $fail) use ($request) {
                    $hasGuardianDetails = $request->filled('guardian_first_name') ||
                                         $request->filled('guardian_last_name') ||
                                         $request->filled('guardian_relationship') ||
                                         $request->filled('guardian_phone_number') ||
                                         $request->filled('guardian_email_address');
                    if ($hasGuardianDetails && !$value) {
                        $fail('The guardian valid ID is required when guardian details are provided.');
                    }
                },
            ],
        ]);

        // Retrieve pending appointment from session, if it exists
        $pendingAppointment = $request->session()->get('pending_appointment');

        return DB::transaction(function () use ($request, $pendingAppointment) {
            $userId = $request->user_id ?: Str::uuid()->toString();

            $user = User::create([
                'user_id' => $userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'suffix' => $request->suffix,
                'age' => $request->age,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion,
                'sex' => $request->sex,
                'occupation' => $request->occupation,
                'email_address' => $request->email_address,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'user_type' => $request->user_type,
                'status' => $request->status,
                'password' => Hash::make($request->password),
            ]);

            event(new Registered($user));

            // Handle Patient-specific logic
            if ($user->user_type === 'Patient') {
                $validIdPath = $request->file('valid_id') ? $request->file('valid_id')->store('valid_ids', 'public') : null;
                Patient::create([
                    'patient_id' => $user->user_id,
                    'guardian_id' => $request->guardian_id,
                    'valid_id' => $validIdPath,
                    'remaining_balance' => 0,
                ]);

                if ($request->filled('guardian_first_name') || $request->filled('guardian_last_name')) {
                    Guardian::create([
                        'guardian_id' => $request->guardian_id ?: Str::uuid()->toString(),
                        'guardian_first_name' => $request->guardian_first_name,
                        'guardian_last_name' => $request->guardian_last_name,
                        'guardian_relationship' => $request->guardian_relationship,
                        'guardian_phone_number' => $request->guardian_phone_number,
                        'guardian_email_address' => $request->guardian_email_address,
                        'guardian_valid_id' => $request->file('guardian_valid_id') ? $request->file('guardian_valid_id')->store('guardians', 'public') : null,
                    ]);
                }

                // Store user_id in session for medical information step
                session(['user_id' => $user->user_id]);

                // Preserve pending_appointment in session
                if ($pendingAppointment) {
                    $request->session()->put('pending_appointment', $pendingAppointment);
                    Log::info('Preserved pending appointment in session after registration:', [
                        'pending_appointment' => $pendingAppointment,
                    ]);
                }

                // Log in the new patient
                Auth::login($user);

                // Redirect to medical-information with pending appointment data
                return redirect()->route('medical-information')
                    ->with([
                        'has_email' => !empty($user->email_address),
                        'has_phone' => !empty($user->phone_number) || (!empty($user->guardian_phone_number)),
                        'appointment' => $pendingAppointment ? [
                            'branch_id' => $pendingAppointment['branch_id'],
                            'branch_name' => $pendingAppointment['branch_name'] ?? null,
                            'dentist_id' => $pendingAppointment['dentist_id'],
                            'dentist_name' => $pendingAppointment['dentist_name'] ?? null,
                            'schedule_id' => $pendingAppointment['schedule_id'],
                            'schedule_time' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('start_time'),
                            'schedule_date' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('schedule_date'),
                            'treatment_ids' => $pendingAppointment['treatment_ids'] ?? [],
                            'treatment_names' => $pendingAppointment['treatment_names'] ?? [],
                        ] : null,
                    ])->with('success', 'Registration successful. Please complete your profile.');
            }

            // For non-Patient users, this route should not be accessible
            Log::error('Invalid user_type for register route', ['user_type' => $user->user_type]);
            return redirect()->route('register')->with('error', 'Invalid user type.');
        });
    }

    public function storeOwnerStaff(Request $request): RedirectResponse
    {
        // Ensure only owners can access this route
        if (Auth::check() && Auth::user()->user_type !== 'Owner') {
            Log::warning('Non-owner user attempted to access owner/register-staff', [
                'user_id' => Auth::id(),
                'user_type' => Auth::user()->user_type,
            ]);
            return redirect()->route('dashboard')->with('error', 'Only owners can register staff.');
        }

        $request->validate([
            'first_name' => 'required|string|min:2|max:70',
            'last_name' => 'required|string|min:2|max:50',
            'middle_name' => 'nullable|string|min:2|max:50',
            'suffix' => 'nullable|in:Jr.,Sr.,II,III,IV,V',
            'age' => 'required|integer|min:1|max:120',
            'birth_date' => 'required|date',
            'religion' => 'nullable|string|max:50',
            'sex' => 'required|in:Male,Female,Other',
            'email_address' => 'required|string|email|max:255|unique:users,email_address',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'user_type' => 'required|in:Dentist,Staff',
            'status' => 'required|in:Active,Inactive',
            'branch_id' => 'required|exists:branches,branch_id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        return DB::transaction(function () use ($request) {
            $userId = Str::uuid()->toString();

            $user = User::create([
                'user_id' => $userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'suffix' => $request->suffix,
                'age' => $request->age,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion,
                'sex' => $request->sex,
                'occupation' => $request->user_type, // Set occupation to user_type for Dentist/Staff
                'email_address' => $request->email_address,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'user_type' => $request->user_type,
                'status' => $request->status,
                'password' => Hash::make($request->password),
            ]);

            event(new Registered($user));

            if ($user->user_type === 'Dentist') {
                Dentist::create([
                    'dentist_id' => $user->user_id,
                    'dentist_type' => 'Dentist',
                ]);
                UserBranch::create([
                    'user_id' => $user->user_id,
                    'branch_id' => $request->branch_id,
                ]);
            } elseif ($user->user_type === 'Staff') {
                Staff::create([
                    'staff_id' => $user->user_id,
                    'staff_type' => 'Receptionist',
                ]);
                UserBranch::create([
                    'user_id' => $user->user_id,
                    'branch_id' => $request->branch_id,
                ]);
            }

            // Clear any pending appointment for non-Patient users
            if ($request->session()->has('pending_appointment')) {
                $request->session()->forget('pending_appointment');
                Log::info('Cleared pending appointment for staff registration', [
                    'user_type' => $user->user_type,
                ]);
            }

            // Set success message based on user_type
            $successMessage = $user->user_type === 'Dentist' 
                ? 'Dentist registered successfully.' 
                : 'Staff member registered successfully.';

            return redirect('/dashboard/owner/records/StaffManagement')
                ->with('success', $successMessage);
        });
    }
}

?>