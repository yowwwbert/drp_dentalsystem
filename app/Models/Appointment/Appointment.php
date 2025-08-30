<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Appointment extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';
    public $incrementing = false; // Disable auto-incrementing since we're using a custom ID
    protected $keyType = 'string'; // Specify that the primary key is a string
    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'schedule_id',
        'branch_id',
        'billing_id',
        'status',
        'notes',
        'status_changed_by',
        'status_changed_at',
        'reason_for_status_change',
        'appointment_created_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'appointment_id' => 'string',
        'patient_id' => 'string',
        'dentist_id' => 'string',
        'schedule_id' => 'string',
        'branch_id' => 'string',
        'billing_id' => 'string',
    ];

    public function dentist()
    {
        return $this->belongsTo('App\Models\Users\Dentist', 'dentist_id', 'dentist_id');
    }
    
    public function schedule()
    {
        return $this->belongsTo('App\Models\Clinic\Schedule', 'schedule_id', 'schedule_id');
    }
    public function branch()
    {
        return $this->belongsTo('App\Models\Clinic\Branches', 'branch_id', 'branch_id');
    }

    public function patient()
    {
        return $this->belongsTo('App\Models\Users\Patient', 'patient_id', 'patient_id');
    }
    

    /**
     * Boot method to generate custom appointment_id when creating a new record
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->appointment_id)) {
                $branchPrefix = substr($model->branch_id ?? 'XXX', 0, 3); // First 3 letters of branch_id, default to 'XXX' if null
                $datePart = date('Ymd'); // e.g., 20250805 for August 5, 2025
                $randomPart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4-digit random number, padded with zeros
                $model->appointment_id = "APT{$branchPrefix}-{$datePart}-{$randomPart}";
            }
        });
    }
}