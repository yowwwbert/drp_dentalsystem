<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment\Appointment;
use App\Models\Pivot\BillingTreatment;



class Billings extends Model
{
    protected $table = 'billings';
    protected $primaryKey = 'billing_id';
    public $incrementing = false;
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
        'discount_amount' => 'decimal:2',
        'billing_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    public function billingTreatments()
{
    return $this->hasMany(\App\Models\Pivot\BillingTreatment::class, 'billing_id');
}


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->billing_id)) {
                $datePart = now()->format('Ymd');
                $randomPart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $baseId = "BILL{$randomPart}{$datePart}";

                $counter = 1;
                $uniqueId = $baseId;
                while (self::where('billing_id', $uniqueId)->exists()) {
                    $randomPart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $uniqueId = "BILL{$randomPart}-{$datePart}";
                    $counter++;
                    if ($counter > 100) {
                        throw new \Exception('Unable to generate a unique billing ID after 100 attempts.');
                    }
                }

                $model->billing_id = $uniqueId;
            }
        });
    }
}