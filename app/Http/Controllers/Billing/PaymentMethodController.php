<?php

namespace App\Http\Controllers\Billing;

use App\Models\Billing\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the payment methods.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::all();
        return response()->json(['data' => $paymentMethods], 200);
    }

    /**
     * Store a newly created payment method in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method_name' => 'required|string|max:255',
            'payment_method_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $paymentMethod = PaymentMethod::create($request->all());
        return response()->json(['data' => $paymentMethod], 201);
    }

    /**
     * Update the specified payment method in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $payment_method_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $payment_method_id)
    {
        $paymentMethod = PaymentMethod::findOrFail($payment_method_id);

        $validator = Validator::make($request->all(), [
            'payment_method_name' => 'required|string|max:255',
            'payment_method_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $paymentMethod->update($request->all());
        return response()->json(['data' => $paymentMethod], 200);
    }
}