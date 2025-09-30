<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Payments extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'billing_id',
        'appointment_id',
        'patient_id',
        'payment_method_id',
        'amount',
        'status',
        'payment_type',
        'payment_date',
        'notes',
        'handled_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date',
        'payment_type' => 'string',
    ];

    /**
     * Boot the model and set up the auto-generation of payment_id.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_id)) {
                // Generate payment_id in format PMT-YYYYMMDD-RRRR
                $date = now()->format('Ymd'); // e.g., 20250928
                $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); // e.g., 1234
                $model->payment_id = "PMT-{$date}-{$random}";

                // Ensure uniqueness
                while (static::where('payment_id', $model->payment_id)->exists()) {
                    $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $model->payment_id = "PMT-{$date}-{$random}";
                }
            }
        });
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'payment_method_id');
    }
    public function billing()
    {
        return $this->belongsTo(Billings::class, 'billing_id', 'billing_id');
    }
    public function appointment()
    {
        return $this->belongsTo('App\Models\Appointment\Appointment', 'appointment_id', 'appointment_id');
    }
    public function patient()
    {
        return $this->belongsTo('App\Models\Users\Patient', 'patient_id', 'patient_id');
    }
    public function handledByUser()
    {
        return $this->belongsTo('App\Models\Users\User', 'handled_by', 'user_id');
    }   
}