<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;

class ToothTreatment extends Model
{
    protected $primaryKey = 'treatment_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'treatment_id',
        'tooth_id',
        'surface_id',
        'tooth_mark_id',
        'dentist_id',
        'appointment_id',
        'treatment_date',
        'treatment_notes',
    ];

    public function tooth()
    {
        return $this->belongsTo(Tooth::class, 'tooth_id', 'tooth_id');
    }

    public function surface()
    {
        return $this->belongsTo(ToothSurface::class, 'surface_id', 'surface_id');
    }

    public function toothMark()
    {
        return $this->belongsTo(ToothMark::class, 'tooth_mark_id', 'tooth_mark_id');
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id', 'user_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }
}