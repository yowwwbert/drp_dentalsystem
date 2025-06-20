<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\MedicalInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class PatientMedicalInfoController extends Controller
{
    /**
     * Show the medical information form.
     */
    public function create()
    {
        return inertia('auth/MedicalInformation'); // Make sure this path matches your Vue file
    }

    /**
     * Store the medical information.
     */
    public function store(Request $request)
    {
        $request->validate([
            'previous_dentist' => 'nullable|string|max:255',
            'last_dental_visit' => 'nullable|date',
            'physician_name' => 'nullable|string|max:255',
            'physician_address' => 'nullable|string|max:255',
            'physician_contact' => 'nullable|string|max:20',
            'physician_specialty' => 'nullable|string|max:100',
            'under_medication' => 'required|in:true,false',
        'congenital_abnormalities' => 'required|in:true,false',
        ]);

        $user = Auth::user();

        // Confirm the user is a patient and has a record
        $patient = Patient::where('patient_id', $user->user_id)->firstOrFail();

        MedicalInformation::create([
            'medical_info_id' => (string) Str::uuid(), // Manually generate string-based UUID
            'patient_id' => $patient->patient_id,
            'previous_dentist' => $request->previous_dentist,
            'last_dental_visit' => $request->last_dental_visit,
            'physician_name' => $request->physician_name,
            'physician_address' => $request->physician_address,
            'physician_contact' => $request->physician_contact,
            'physician_specialty' => $request->physician_specialty,
            'under_medication' => $request->under_medication === 'true',
'congenital_abnormalities' => $request->congenital_abnormalities === 'true',

        ]);

        return redirect()->route('verification.notice'); // or redirect to dashboard if already verified
    }
}
