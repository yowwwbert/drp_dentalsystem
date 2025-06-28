<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Models\Users\Patient;
use App\Models\Users\Dentist;
use App\Models\Users\Staff;
use App\Models\Users\Owner;
use App\Models\PatientDetails\Guardian;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        // Validate registration fields
        $request->validate([
            'first_name'      => 'required|string|min:2|max:70',
            'last_name'       => 'required|string|min:2|max:50',
            'middle_name'     => 'nullable|string|min:2|max:50',
            'suffix'          => 'nullable|in:Jr.,Sr.,II,III,IV,V',
            'age'             => 'required|integer|min:1|max:120',
            'birth_date'      => 'required|date',
            'religion'        => 'nullable|string|max:50',
            'sex'             => 'required|in:Male,Female,Other',
            'occupation'      => 'nullable|string|min:2|max:100',
            'email_address'   => 'nullable|string|email|max:255|unique:users,email_address',
            'phone_number'    => 'nullable|string|max:20',
            'address'         => 'required|string|max:255',
            'user_type'       => 'required|in:Patient,Dentist,Staff,Owner',
            'status'          => 'required|in:Active,Inactive',
            'valid_id'        => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048', 'required_if:user_type,Patient'],
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
            'guardian_first_name'    => 'nullable|string|max:70',
            'guardian_last_name'     => 'nullable|string|max:70',
            'guardian_relationship'  => 'nullable|string|max:50',
            'guardian_phone_number'  => 'nullable|string|max:20',
            'guardian_email_address' => 'nullable|string|email|max:255',
            'guardian_valid_id'      => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048', 'required_if:user_type,Patient'],
        ]);

        // Use a transaction for data consistency
        return DB::transaction(function () use ($request) {
            // Create the user account
            $user = User::create([
                'user_id'         => $request->user_id, // Generate a UUID if not provided
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'middle_name'     => $request->middle_name,
                'suffix'          => $request->suffix,
                'age'             => $request->age,
                'birth_date'      => $request->birth_date,
                'religion'        => $request->religion,
                'sex'             => $request->sex,
                'occupation'      => $request->occupation,
                'email_address'   => $request->email_address,
                'phone_number'    => $request->phone_number,
                'address'         => $request->address,
                'user_type'       => $request->user_type,
                'status'          => $request->status,
                'password'        => Hash::make($request->password),
            ]);

            event(new Registered($user));
            Auth::login($user);

            // Create related records based on user_type
            if ($user->user_type === 'Patient') {
                $validIdPath = $request->file('valid_id')->store('valid_ids', 'public'); // Required for Patient
                Patient::create([
                    'patient_id'        => $user->user_id,
                    'guardian_id'       => $request->guardian_id,
                    'valid_id'          => $validIdPath,
                    'remaining_balance' => 0,
                ]);
                Guardian::create([
                    'guardian_id'          => $request->guardian_id, // Use user_id if guardian_id is not provided
                    'guardian_first_name'  => $request->guardian_first_name,
                    'guardian_last_name'   => $request->guardian_last_name,
                    'guardian_relationship' => $request->guardian_relationship,
                    'guardian_phone_number' => $request->guardian_phone_number,
                    'guardian_email_address'=> $request->guardian_email_address,
                    'guardian_valid_id'    => $request->file('valid_id')->store('guardians', 'public'),
                ]);
            } elseif ($user->user_type === 'Dentist') {
                Dentist::create([
                    'dentist_id'        => $user->user_id,
                    'dentist_type'      => 'Dentist',
                ]);
            } elseif ($user->user_type === 'Staff') {
                Staff::create([
                    'staff_id'          => $user->user_id,
                    'staff_type'        => 'Receptionist',
                ]);
            } elseif ($user->user_type === 'Owner') {
                Owner::create([
                    'owner_id'          => $user->user_id,
                ]);
            }

            // Redirect based on user_type
            return $user->user_type === 'Patient'
                ? redirect()->route('medical-information')
                : redirect()->route('verification.notice');
        });
    }
}