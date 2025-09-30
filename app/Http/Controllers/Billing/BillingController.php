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
            'request_parameters' => $request->query(),
        ]);

        // Include appointments relationship
        $billings = Billings::query()->with(['patient.user', 'appointments'])->get();

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
            
            $updatedBalance = Patient::Where('patient_id', $validated['patient_id'])
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
        $billing = Billings::where('billing_id', $billingId)->firstOrFail();

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

        try {
            DB::beginTransaction();

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