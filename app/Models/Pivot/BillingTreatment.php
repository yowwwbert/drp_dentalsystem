<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingTreatment extends Model
{
    use HasFactory;

    protected $table = 'billing_treatments';

    protected $fillable = [
        'billing_id',
        'treatment_id',
        'dentist_id',
        'price',
        'discount_amount',
        'discount_reason',
    ];

    /**
     * Relationships
     */

    // A billing_treatment belongs to a billing
    public function billing()
    {
        return $this->belongsTo(\App\Models\Billing\Billing::class, 'billing_id');
    }

    // A billing_treatment belongs to a treatment
    public function treatment()
    {
        return $this->belongsTo(\App\Models\Clinic\Treatment::class, 'treatment_id');
    }

    // A billing_treatment may belong to a dentist
    public function dentist()
    {
        return $this->belongsTo(\App\Models\Users\Dentist::class, 'dentist_id');
    }
}
