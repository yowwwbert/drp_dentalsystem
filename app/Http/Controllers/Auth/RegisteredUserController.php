<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\Dentist;
use App\Models\Staff;
use App\Models\Owner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
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
            'occupation' => 'nullable|string|min:2|max:100',
            'email_address'   => 'nullable|string|email|max:255|unique:users,email_address',
            'phone_number'    => 'nullable|string|max:20',
            'address'         => 'required|string|max:255',
            'user_type'       => 'required|in:Patient,Dentist,Staff,Owner',
            'status'          => 'required|in:Active,Inactive',
            'valid_id'        => 'required|file|mimes:jpg,png,jpeg|max:2048',
            'password'        => ['required', 'confirmed', Rules\Password::defaults()],
        ]);


        $validIdPath = $request->file('valid_id')->store('valid_ids', 'public');

        // Create the user account
        $user = User::create([
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
            'valid_id'        => $validIdPath,
            'password'        => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        if ($user->user_type === 'Patient') {
            Patient::create([
                'patient_id'        => $user->user_id,
                'guardian_id'       => null,
                'remaining_balance' => 0,
            ]);
        } else if ($user->user_type === 'Dentist') {
            Dentist::create([
                'dentist_id'        => $user->user_id,
                'dentist_type'      => 'Dentist',
            ]);
        } else if ($user->user_type === 'Staff') {
            Staff::create([
                'staff_id'          => $user->user_id,
                'staff_type'        => 'Receptionist', // Default to Receptionist, can be changed later
            ]);
            // Handle Staff specific logic if needed
        } else if ($user->user_type === 'Owner') {
            Owner::create([
                'owner_id'        => $user->user_id,
            ]);
        }

        // Redirect to email verification notice
        if ($user->user_type === 'Patient') {
            return redirect()->route('medical-information');
        } else {
            return redirect()->route('dashboard');
        }
    }
}
