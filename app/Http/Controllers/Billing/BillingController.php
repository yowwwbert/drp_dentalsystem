<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Billings;
use App\Models\Appointment\Appointment;
use App\Models\Users\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function fetchAll(Request $request)
    {
        Log::info('Fetching all billings', [
            'user_id' => auth()->user()->id ?? 'N/A',
            'user_type' => auth()->user()->user_type ?? 'N/A',
            'request_parameters' => $request->query(),
        ]);

        $query = Billings::query()->with(['patient.user', 'appointments']);

        $user = auth()->user();

        // Apply user-based filtering
        if ($user->user_type === 'Patient') {
            $query->where('patient_id', $user->id);
            Log::info("Filtering billings for patient_id: {$user->id}");
        } elseif ($user->user_type === 'Dentist') {
            $query->whereHas('appointments', function ($q) use ($user) {
                $q->where('dentist_id', $user->id);
            });
            Log::info("Filtering billings for dentist_id: {$user->id}");
        } elseif ($user->user_type === 'Receptionist' && $user->branch_id) {
            $query->whereHas('appointments', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
            Log::info("Filtering billings for branch_id: {$user->branch_id}");
        } elseif ($user->user_type === 'Owner') {
            Log::info("No filters applied for Owner, fetching all billings");
        } else {
            Log::warning("Unauthorized user type: {$user->user_type}");
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        // Apply additional query parameter filters (optional, for flexibility)
        if ($request->has('patient_id') && !empty($request->query('patient_id')) && $user->user_type === 'Owner') {
            $patientId = $request->query('patient_id');
            $query->where('patient_id', $patientId);
            Log::info("Additional filter applied for patient_id: {$patientId}");
        }

        $billings = $query->get();

        Log::info('Raw billings data retrieved:', [
            'total_records' => $billings->count(),
            'billings' => $billings->toArray(),
        ]);

        $formattedData = $billings->map(function ($billing) {
            $user = $billing->patient && $billing->patient->user ? $billing->patient->user : null;
            $patientName = 'N/A';

            if ($user) {
                $nameParts = array_filter([
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name,
                    $user->suffix,
                ]);
                $patientName = implode(' ', $nameParts);
            }

            return [
                'billing_id'      => $billing->billing_id,
                'patient_id'      => $billing->patient_id,
                'patient_name'    => $patientName,
                'billing_date'    => $billing->billing_date,
                'amount'          => $billing->amount,
                'status'          => $billing->status,
                'procedures'      => $billing->procedures ?? [],
                'discount_amount' => $billing->discount_amount ?? [],
                'discount_reason' => $billing->discount_reason ?? [],
                'appointments'    => $billing->appointments->map(function ($appointment) {
                    return [
                        'appointment_id' => $appointment->appointment_id,
                        'date'           => $appointment->date,
                        'time'           => $appointment->time,
                        'services'       => $appointment->services ?? [],
                        'status'         => $appointment->status,
                    ];
                })->toArray(),
            ];
        })->toArray();

        Log::info('Transformed billings data:', [
            'formatted_data' => $formattedData,
        ]);

        return response()->json([
            'data'          => $formattedData,
            'total_records' => count($formattedData),
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        // Restrict Patients from creating billings
        if ($user->user_type === 'Patient') {
            Log::warning("Patient attempted to create billing", ['user_id' => $user->id]);
            return response()->json(['error' => 'Unauthorized: Patients cannot create billings'], 403);
        }

        $validated = $request->validate([
            'patient_id' => 'required|string|exists:patients,patient_id',
            'billing_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:Pending,Partially Paid,Paid',
            'procedures' => 'required|array|min:1',
            'procedures.*.name' => 'required|string',
            'procedures.*.description' => 'nullable|string',
            'procedures.*.unit_price' => 'required|numeric|min:0',
            'procedures.*.total' => 'required|numeric|min:0',
            'procedures.*.discount_amount' => 'nullable|numeric|min:0',
            'procedures.*.discount_reason' => 'nullable|string',
            'discount_amount' => 'nullable|array',
            'discount_reason' => 'nullable|array',
            'appointment_ids' => 'required|array|min:1',
            'appointment_ids.*' => 'required|string|exists:appointments,appointment_id',
        ]);

        // Verify branch_id for Receptionist and Dentist
        if (in_array($user->user_type, ['Receptionist', 'Dentist']) && $user->branch_id) {
            $appointments = Appointment::whereIn('appointment_id', $validated['appointment_ids'])->get();
            foreach ($appointments as $appointment) {
                if ($appointment->branch_id !== $user->branch_id) {
                    Log::warning("Unauthorized: Appointment not in user's branch", [
                        'user_id' => $user->id,
                        'branch_id' => $user->branch_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    return response()->json(['error' => 'Unauthorized: Appointment not in your branch'], 403);
                }
            }
        }

        try {
            DB::beginTransaction();

            $validated['billing_id'] = Str::uuid()->toString();

            $billing = Billings::create([
                'billing_id'      => $validated['billing_id'],
                'patient_id'      => $validated['patient_id'],
                'billing_date'    => $validated['billing_date'],
                'amount'          => $validated['amount'],
                'status'          => $validated['status'],
                'procedures'      => $validated['procedures'],
                'discount_amount' => $validated['discount_amount'],
                'discount_reason' => $validated['discount_reason'],
            ]);

            // Update appointments with billing_id
            $updated = Appointment::whereIn('appointment_id', $validated['appointment_ids'])
                ->whereNull('billing_id')
                ->update(['billing_id' => $billing->billing_id]);

            if ($updated === 0) {
                throw new \Exception('No eligible appointments were updated. All provided appointments may already be linked to a billing.');
            }

            $updatedBalance = Patient::where('patient_id', $validated['patient_id'])
                ->increment('remaining_balance', $validated['amount']);

            DB::commit();

            return response()->json(['data' => $billing], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating billing: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception'    => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to create billing: ' . $e->getMessage()], 422);
        }
    }

    public function update(Request $request, $billingId)
    {
        $user = auth()->user();

        // Restrict Patients from updating billings
        if ($user->user_type === 'Patient') {
            Log::warning("Patient attempted to update billing", ['user_id' => $user->id, 'billing_id' => $billingId]);
            return response()->json(['error' => 'Unauthorized: Patients cannot update billings'], 403);
        }

        $billing = Billings::where('billing_id', $billingId)->firstOrFail();

        // Verify branch_id for Receptionist and Dentist
        if (in_array($user->user_type, ['Receptionist', 'Dentist']) && $user->branch_id) {
            $appointments = $billing->appointments;
            foreach ($appointments as $appointment) {
                if ($appointment->branch_id !== $user->branch_id) {
                    Log::warning("Unauthorized: Billing not in user's branch", [
                        'user_id' => $user->id,
                        'branch_id' => $user->branch_id,
                        'billing_id' => $billingId,
                    ]);
                    return response()->json(['error' => 'Unauthorized: Billing not in your branch'], 403);
                }
            }
        }

        $validated = $request->validate([
            'patient_id' => 'required|string|exists:patients,patient_id',
            'billing_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:Pending,Partially Paid,Paid',
            'procedures' => 'required|array|min:1',
            'procedures.*.name' => 'required|string',
            'procedures.*.description' => 'nullable|string',
            'procedures.*.unit_price' => 'required|numeric|min:0',
            'procedures.*.total' => 'required|numeric|min:0',
            'procedures.*.discount_amount' => 'nullable|numeric|min:0',
            'procedures.*.discount_reason' => 'nullable|string',
            'discount_amount' => 'nullable|array',
            'discount_reason' => 'nullable|array',
            'appointment_ids' => 'required|array|min:1',
            'appointment_ids.*' => 'required|string|exists:appointments,appointment_id',
        ]);

        // Verify branch_id for new appointment_ids
        if (in_array($user->user_type, ['Receptionist', 'Dentist']) && $user->branch_id) {
            $appointments = Appointment::whereIn('appointment_id', $validated['appointment_ids'])->get();
            foreach ($appointments as $appointment) {
                if ($appointment->branch_id !== $user->branch_id) {
                    Log::warning("Unauthorized: New appointment not in user's branch", [
                        'user_id' => $user->id,
                        'branch_id' => $user->branch_id,
                        'appointment_id' => $appointment->appointment_id,
                    ]);
                    return response()->json(['error' => 'Unauthorized: Appointment not in your branch'], 403);
                }
            }
        }

        try {
            DB::beginTransaction();

            // Update patient balance (subtract old amount, add new amount)
            $oldAmount = $billing->amount;
            Patient::where('patient_id', $billing->patient_id)
                ->decrement('remaining_balance', $oldAmount);
            Patient::where('patient_id', $validated['patient_id'])
                ->increment('remaining_balance', $validated['amount']);

            $billing->update($validated);

            // Update appointments to point to this billing
            Appointment::whereIn('appointment_id', $validated['appointment_ids'])
                ->update(['billing_id' => $billing->billing_id]);

            DB::commit();

            return response()->json(['data' => $billing]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating billing: ' . $e->getMessage(), [
                'billing_id'   => $billingId,
                'request_data' => $request->all(),
                'exception'    => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to update billing: ' . $e->getMessage()], 500);
        }
    }
}