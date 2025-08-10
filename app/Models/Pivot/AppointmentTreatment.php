<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment\Appointment;
use App\Models\Clinic\Treatment;

class AppointmentTreatment extends Model
{
    
    protected $table = 'appointment_treatments';
    public $timestamps = false; // Assuming you don't need timestamps for this pivot table
    public $incrementing = false; // Disable auto-incrementing since we're using a composite key
    protected $fillable = ['appointment_id', 'treatment_id'];


    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class, 'treatment_id', 'treatment_id');
    }
}
