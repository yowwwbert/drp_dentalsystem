<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Billings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function fetchAll(Request $request)
    {
        try {
            Log::info('Fetching all billings', [
                'user_id' => auth()->id() ?? 'N/A',
                'user_type' => auth()->user()->user_type ?? 'Unknown',
                'request_parameters' => $request->all(),
            ]);

            $query = Billings::query()->with([
                'patient.user',
                'appointments',
                'billingTreatments.treatment',
                'billingTreatments.dentist.user',
            ]);

            if (auth()->user()->user_type === 'Patient') {
                $query->where('patient_id', auth()->id());
            } elseif (auth()->user()->user_type === 'Dentist') {
                $query->whereHas('billingTreatments', function ($q) {
                    $q->where('dentist_id', auth()->id());
                });
            } elseif (auth()->user()->user_type === 'Receptionist' && auth()->user()->branch_id) {
                $query->whereHas('appointments', function ($q) {
                    $q->where('branch_id', auth()->user()->branch_id);
                });
            } else {
                Log::info('No filters applied for Owner, fetching all billings');
            }

            $billings = $query->get();
            Log::info('Raw billings data retrieved', ['total_records' => $billings->count()]);

            $formattedBillings = $billings->map(function ($billing) {
                return [
                    'billing_id' => $billing->billing_id,
                    'patient_id' => $billing->patient_id,
                    'patient_name' => $billing->patient && $billing->patient->user
                        ? $billing->patient->user->first_name . ' ' . $billing->patient->user->last_name
                        : 'Unknown Patient',
                    'billing_date' => $billing->billing_date,
                    'amount' => $billing->amount ?? 0,
                    'status' => $billing->status,
                    'discount_amount' => $billing->discount_amount ?? 0,
                    'discount_reason' => $billing->discount_reason ?? '',
                    'treatments' => $billing->billingTreatments
                        ? $billing->billingTreatments->map(function ($treatment) {
                            return [
                                'billing_treatment_id' => $treatment->billing_treatment_id,
                                'treatment_id' => $treatment->treatment_id,
                                'treatment_name' => $treatment->treatment ? $treatment->treatment->name : 'N/A',
                                'dentist_id' => $treatment->dentist_id,
                                'dentist_name' => $treatment->dentist && $treatment->dentist->user
                                    ? $treatment->dentist->user->first_name . ' ' . $treatment->dentist->user->last_name
                                    : 'N/A',
                                'description' => $treatment->description ?? 'N/A',
                                'price' => $treatment->price ?? 0,
                                'discount_amount' => $treatment->discount_amount ?? 0,
                                'discount_reason' => $treatment->discount_reason ?? '',
                                'total' => $treatment->total ?? 0,
                            ];
                        })->toArray()
                        : [],
                    'appointments' => $billing->appointments
                        ? $billing->appointments->map(function ($appointment) {
                            return [
                                'appointment_id' => $appointment->appointment_id,
                                'date' => $appointment->date,
                                'start_time' => $appointment->start_time,
                                'status' => $appointment->status,
                                'branch_id' => $appointment->branch_id,
                                'branch_name' => $appointment->branch ? $appointment->branch->name : 'N/A',
                                'services' => $appointment->services ?? [],
                            ];
                        })->toArray()
                        : [],
                ];
            })->toArray();

            return response()->json([
                'data' => $formattedBillings,
                'total_records' => $billings->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching billings: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Failed to fetch billings'], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            Log::info('Creating new billing', [
                'user_id' => auth()->id() ?? 'N/A',
                'request_data' => $request->all(),
            ]);

            // Validate request
            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,patient_id',
                'billing_date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'status' => 'required|in:Pending,Partially Paid,Paid',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_reason' => 'nullable|string',
                'treatments' => 'required|array|min:1',
                'treatments.*.treatment_id' => 'required|exists:treatments,treatment_id',
                'treatments.*.dentist_id' => 'required|exists:dentists,dentist_id',
                'treatments.*.description' => 'nullable|string',
                'treatments.*.price' => 'required|numeric|min:0',
                'treatments.*.discount_amount' => 'nullable|numeric|min:0',
                'treatments.*.discount_reason' => 'nullable|string',
                'treatments.*.total' => 'required|numeric|min:0',
                'appointment_ids' => 'required|array|min:1',
                'appointment_ids.*' => 'required|exists:appointments,appointment_id',
            ]);

            // Start transaction
            \DB::beginTransaction();

            // Create billing record
            $billing = Billings::create([
                'patient_id' => $validated['patient_id'],
                'billing_date' => $validated['billing_date'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_reason' => $validated['discount_reason'] ?? null,
            ]);

            Log::info('Billing record created', ['billing_id' => $billing->billing_id]);

            // Create billing treatments
            foreach ($validated['treatments'] as $treatment) {
                $billing->billingTreatments()->create([
                    'billing_id' => $billing->billing_id, // Use the created billing ID
                    'treatment_id' => $treatment['treatment_id'],
                    'dentist_id' => $treatment['dentist_id'],
                    'description' => $treatment['description'] ?? null, // Include description
                    'price' => $treatment['price'],
                    'discount_amount' => $treatment['discount_amount'] ?? 0,
                    'discount_reason' => $treatment['discount_reason'] ?? null,
                    'total' => $treatment['total'], // Include total
                ]);
            }

            Log::info('Billing treatments created', ['count' => count($validated['treatments'])]);

            // Update appointments with billing_id
            \DB::table('appointments')
                ->whereIn('appointment_id', $validated['appointment_ids'])
                ->update(['billing_id' => $billing->billing_id]);

            Log::info('Appointments updated with billing_id', [
                'billing_id' => $billing->billing_id,
                'appointment_ids' => $validated['appointment_ids'],
            ]);

            // Commit transaction
            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Billing created successfully',
                'billing_id' => $billing->billing_id,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            Log::warning('Validation failed for billing creation', ['errors' => $e->errors()]);
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error creating billing: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Failed to create billing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}