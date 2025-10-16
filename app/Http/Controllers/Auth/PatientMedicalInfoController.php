<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Users\Patient;
use App\Models\PatientDetails\MedicalInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\Clinic\Schedule; 
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PatientMedicalInfoController extends Controller
{
    /**
     * Show the medical information form.
     */
    public function create()
    {
        return Inertia::render('auth/MedicalInformation');
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

        $userId = session('user_id');
        if (!$userId) {
            abort(403, 'Unauthorized: No patient session found.');
        }

        $patient = Patient::where('patient_id', $userId)->firstOrFail();
        $userModel = $patient->user; // Ensure Patient model has belongsTo(User::class, 'patient_id', 'user_id')

        // Retrieve pending appointment from session
        $pendingAppointment = $request->session()->get('pending_appointment');

        Log::info('Storing medical information for patient:', [
            'patient_id' => $patient->patient_id,
            'pending_appointment' => $pendingAppointment ?? 'None',
        ]);

        MedicalInformation::create([
            'medical_info_id' => (string) Str::uuid(),
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

        Auth::login($userModel);

        // Preserve pending_appointment in session
        if ($pendingAppointment) {
            $request->session()->put('pending_appointment', $pendingAppointment);
            Log::info('Preserved pending appointment in session after medical information:', [
                'pending_appointment' => $pendingAppointment,
            ]);
        }

        // Do not clear url.intended to preserve intended redirect, if any
        // Session::forget('url.intended');

        // Prepare appointment data for verification step using pending_appointment
        $appointmentData = $pendingAppointment ? [
            'branch_id' => $pendingAppointment['branch_id'],
                'branch_name' => $pendingAppointment['branch_name'],
                'dentist_id' => $pendingAppointment['dentist_id'],
                'dentist_name' => $pendingAppointment['dentist_name'],
                'schedule_id' => $pendingAppointment['schedule_id'],
                'schedule_time' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('start_time'),
                'schedule_date' => Schedule::where('schedule_id', $pendingAppointment['schedule_id'])->value('schedule_date'),
                'treatment_ids' => $pendingAppointment['treatment_ids'] ?? [],
                'treatment_names' => $pendingAppointment['treatment_names'] ?? [],
        ] : null;

        return redirect()->route(
            $userModel && $userModel->email_address ? 'verification.notice' : 'phone.verify'
        )->with([
            'has_email' => !empty($userModel->email_address),
            'has_phone' => !empty($userModel->phone_number),
            'appointment' => $appointmentData,
        ])->with('success', 'Medical information saved. Please verify your account.');
    }
}