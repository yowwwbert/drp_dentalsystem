<?php

namespace App\Http\Controllers\Billing;

use App\Models\Billing\Payments as Payment;
use App\Models\Billing\Billings;
use App\Models\Appointment\Appointment;
use App\Models\Users\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(): JsonResponse
    {
        try {
            $payments = Payment::with([
                'appointment' => function ($query) {
                    $query->select('appointment_id', 'patient_id', 'schedule_id', 'status')
                          ->with(['schedule' => function ($subQuery) {
                              $subQuery->select('schedule_id', 'schedule_date', 'start_time');
                          }]);
                },
                'patient' => function ($query) {
                    $query->select('patients.patient_id')
                          ->join('users', 'patients.patient_id', '=', 'users.user_id')
                          ->addSelect('users.first_name', 'users.last_name');
                },
                'paymentMethod' => function ($query) {
                    $query->select('payment_method_id', 'payment_method_name');
                },
                'handledByUser' => function ($query) {
                    $query->select('user_id', 'first_name', 'last_name');
                },
                'billing' => function ($query) {
                    $query->select('billing_id', 'patient_id', 'amount', 'billing_date', 'status');
                }
            ])->get();

            $data = $payments->map(function ($payment) {
                return [
                    'payment_id' => $payment->payment_id,
                    'billing_id' => $payment->billing_id,
                    'appointment_id' => $payment->appointment_id,
                    'patient_id' => $payment->patient_id,
                    'patient_name' => $payment->patient 
                        ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                        : 'N/A',
                    'payment_method_id' => $payment->payment_method_id,
                    'payment_method_name' => $payment->paymentMethod ? $payment->paymentMethod->payment_method_name : 'N/A',
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->toDateString(),
                    'status' => $payment->status,
                    'payment_type' => $payment->payment_type,
                    'notes' => $payment->notes,
                    'handled_by' => $payment->handledByUser->last_name . ', ' . $payment->handledByUser->first_name, // Return raw user_id
                    'appointment_details' => $payment->appointment && $payment->appointment->schedule ? [
                            'appointment_id' => $payment->appointment->appointment_id,
                        'schedule_date' => $payment->appointment->schedule->schedule_date,
                        'start_time' => $payment->appointment->schedule->start_time,
                        'services' => $payment->appointment->treatments->pluck('treatment_name')->toArray(),
                        'status' => $payment->appointment->status,
                        'patient_name' => $payment->patient 
                            ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                            : 'N/A',
                    ] : null,
                ];
            });

            return response()->json([
                'data' => $data,
                'total_records' => $payments->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching payments: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created payment and decrement patient balance if completed.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'billing_id' => 'required|string|exists:billings,billing_id',
                'appointment_id' => 'required|string|exists:appointments,appointment_id',
                'patient_id' => 'required|string|exists:patients,patient_id',
                'payment_method_id' => 'required|string|exists:payment_methods,payment_method_id',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date_format:Y-m-d',
                'status' => 'required|in:Pending,Completed,Failed,Refunded',
                'payment_type' => 'required|in:Partial,Full,Advance',
                'notes' => 'nullable|string',
                'handled_by' => 'required|string|exists:users,user_id', // Validate handled_by
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            return DB::transaction(function () use ($request, $validator) {
                $data = $validator->validated();
                $data['payment_id'] = 'PAY-' . uniqid(); // Generate unique payment_id
                $payment = Payment::create($data);

                // Decrement patient balance for completed payments
                if ($data['status'] === 'Completed') {
                    $updatedRows = Patient::where('patient_id', $data['patient_id'])
                        ->decrement('remaining_balance', $data['amount']);
                    if ($updatedRows === 0) {
                        throw new \Exception('Patient not found or balance update failed.');
                    }
                }

                // Load relationships for response
                $payment->load([
                    'appointment' => function ($query) {
                        $query->select('appointment_id', 'patient_id', 'schedule_id', 'status')
                              ->with(['schedule' => function ($subQuery) {
                                  $subQuery->select('schedule_id', 'schedule_date', 'start_time');
                              }]);
                    },
                    'patient' => function ($query) {
                        $query->select('patients.patient_id')
                              ->join('users', 'patients.patient_id', '=', 'users.user_id')
                              ->addSelect('users.first_name', 'users.last_name');
                    },
                    'paymentMethod' => function ($query) {
                        $query->select('payment_method_id', 'payment_method_name');
                    },
                    'handledByUser' => function ($query) {
                        $query->select('user_id', 'first_name', 'last_name');
                    },
                    'billing' => function ($query) {
                        $query->select('billing_id', 'patient_id', 'amount', 'billing_date', 'status');
                    }
                ]);

                return response()->json([
                    'data' => [
                        'payment_id' => $payment->payment_id,
                        'billing_id' => $payment->billing_id,
                        'appointment_id' => $payment->appointment_id,
                        'patient_id' => $payment->patient_id,
                        'patient_name' => $payment->patient 
                            ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                            : 'N/A',
                        'payment_method_id' => $payment->payment_method_id,
                        'payment_method_name' => $payment->paymentMethod ? $payment->paymentMethod->payment_method_name : 'N/A',
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date->toDateString(),
                        'status' => $payment->status,
                        'payment_type' => $payment->payment_type,
                        'notes' => $payment->notes,
                        'handled_by' => $payment->handled_by, // Return raw user_id
                        'appointment_details' => $payment->appointment && $payment->appointment->schedule ? [
                            'appointment_id' => $payment->appointment->appointment_id,
                            'schedule_date' => $payment->appointment->schedule->schedule_date,
                            'start_time' => $payment->appointment->schedule->start_time,
                            'services' => $payment->appointment->treatments->pluck('treatment_name')->toArray(),
                            'status' => $payment->appointment->status,
                            'patient_name' => $payment->patient 
                                ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                                : 'N/A',
                        ] : null,
                    ]
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Error creating payment: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing payment.
     */
    public function update(Request $request, $payment_id): JsonResponse
    {
        try {
            $payment = Payment::where('payment_id', $payment_id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'billing_id' => 'required|string|exists:billings,billing_id',
                'appointment_id' => 'required|string|exists:appointments,appointment_id',
                'patient_id' => 'required|string|exists:patients,patient_id',
                'payment_method_id' => 'required|string|exists:payment_methods,payment_method_id',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date_format:Y-m-d',
                'status' => 'required|in:Pending,Completed,Failed,Refunded',
                'payment_type' => 'required|in:Partial,Full,Advance',
                'notes' => 'nullable|string',
                'handled_by' => 'required|string|exists:users,user_id', // Validate handled_by
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            return DB::transaction(function () use ($request, $payment, $validator) {
                $data = $validator->validated();
                $originalStatus = $payment->status;
                $originalAmount = $payment->amount;
                $originalPatientId = $payment->patient_id;

                $payment->update($data);

                // Adjust patient balance
                if ($originalStatus === 'Completed' && $data['status'] !== 'Completed') {
                    Patient::where('patient_id', $originalPatientId)
                        ->increment('remaining_balance', $originalAmount);
                } elseif ($data['status'] === 'Completed') {
                    Patient::where('patient_id', $data['patient_id'])
                        ->decrement('remaining_balance', $data['amount']);
                }

                // Load relationships for response
                $payment->load([
                    'appointment' => function ($query) {
                        $query->select('appointment_id', 'patient_id', 'schedule_id', 'status')
                              ->with(['schedule' => function ($subQuery) {
                                  $subQuery->select('schedule_id', 'schedule_date', 'start_time');
                              }]);
                    },
                    'patient' => function ($query) {
                        $query->select('patients.patient_id')
                              ->join('users', 'patients.patient_id', '=', 'users.user_id')
                              ->addSelect('users.first_name', 'users.last_name');
                    },
                    'paymentMethod' => function ($query) {
                        $query->select('payment_method_id', 'payment_method_name');
                    },
                    'handledByUser' => function ($query) {
                        $query->select('user_id', 'first_name', 'last_name');
                    },
                    'billing' => function ($query) {
                        $query->select('billing_id', 'patient_id', 'amount', 'billing_date', 'status');
                    }
                ]);

                return response()->json([
                    'data' => [
                        'payment_id' => $payment->payment_id,
                        'billing_id' => $payment->billing_id,
                        'appointment_id' => $payment->appointment_id,
                        'patient_id' => $payment->patient_id,
                        'patient_name' => $payment->patient 
                            ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                            : 'N/A',
                        'payment_method_id' => $payment->payment_method_id,
                        'payment_method_name' => $payment->paymentMethod ? $payment->paymentMethod->payment_method_name : 'N/A',
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date->toDateString(),
                        'status' => $payment->status,
                        'payment_type' => $payment->payment_type,
                        'notes' => $payment->notes,
                        'handled_by' => $payment->handled_by, // Return raw user_id
                        'appointment_details' => $payment->appointment && $payment->appointment->schedule ? [
                            'appointment_id' => $payment->appointment->appointment_id,
                            'schedule_date' => $payment->appointment->schedule->schedule_date,
                            'start_time' => $payment->appointment->schedule->start_time,
                            'services' => $payment->appointment->treatments->pluck('treatment_name')->toArray(),
                            'status' => $payment->appointment->status,
                            'patient_name' => $payment->patient 
                                ? ($payment->patient->last_name . ', ' . $payment->patient->first_name)
                                : 'N/A',
                        ] : null,
                    ]
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error updating payment: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}