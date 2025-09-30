<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Appointment\Appointment;

class Billings extends Model
{
    protected $table = 'billings'; // Updated to match migration table name
    protected $primaryKey = 'billing_id';
    public $incrementing = false; // Since billing_id is varchar, not auto-incrementing
    protected $keyType = 'string';

    protected $fillable = [
        'patient_id',
        'amount',
        'billing_date',
        'discount_amount',
        'discount_reason',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'billing_date' => 'date',
        'discount_amount' => 'array', // Cast JSON to array for easier manipulation
        'discount_reason' => 'array', // Cast JSON to array for easier manipulation
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: One Billing can have many Payments
    public function payments()
    {
        return $this->hasMany(Payment::class, 'billing_id', 'billing_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Models\Users\Patient', 'patient_id', 'patient_id');
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'billing_id', 'billing_id');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Generate custom billing_id before creating a new record
        static::creating(function ($model) {
            if (empty($model->billing_id)) {
                $datePart = now()->format('Ymd'); // e.g., 20250928 for today (04:37 AM PST, Sept 28, 2025)
                $randomPart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4-digit random number (0000-9999)

                $baseId = "BILL{$randomPart}{$datePart}";

                // Ensure uniqueness by checking and appending a counter if needed
                $counter = 1;
                $uniqueId = $baseId;
                while (self::where('billing_id', $uniqueId)->exists()) {
                    $randomPart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $uniqueId = "BILL{$randomPart}-{$datePart}";
                    $counter++;
                    if ($counter > 100) { // Prevent infinite loop
                        throw new \Exception('Unable to generate a unique billing ID after 100 attempts.');
                    }
                }

                $model->billing_id = $uniqueId;
            }

            // Initialize discount_amount and discount_reason as arrays if not set
            if (empty($model->discount_amount)) {
                $model->discount_amount = []; // Default to empty array for JSON
            }
            if (empty($model->discount_reason)) {
                $model->discount_reason = []; // Default to empty array for JSON
            }
        });
    }
}