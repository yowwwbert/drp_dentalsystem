<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Users\Patient;
use Illuminate\Support\Facades\Log;

class PatientListController extends Controller
{
    public function index()
    {
        $patients = Patient::with('user')
            ->select('patient_id')
            ->get()
            ->map(function ($patient) {
                return [
                    'patient_id'    => $patient->patient_id,
                    'first_name'    => $patient->user?->first_name ?? '',
                    'last_name'     => $patient->user?->last_name ?? '',
                    'email_address' => $patient->user?->email_address ?? '',
                    'phone_number'  => $patient->user?->phone_number ?? '',
                    'age'           => $patient->user?->age ?? null,
                    'sex'           => $patient->user?->sex ?? '',
                    'created_at'    => $patient->user?->created_at ?? '',
                    'balance'       => $patient->remaining_balance ?? 0,

                ];
            });

        Log::info('Retrieved patients:', $patients->toArray());

        return response()->json($patients);
    }
}