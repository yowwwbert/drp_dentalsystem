<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalInformation extends Model
{
    protected $table = 'medical_information';
    protected $primaryKey = 'medical_info_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'medical_info_id',
        'patient_id',
        'previous_dentist',
        'last_dental_visit',
        'physician_name',
        'physician_address',
        'physician_contact',
        'physician_specialty',
        'under_medication',
        'congenital_abnormalities',
    ];

    protected $casts = [
        'medical_info_id' => 'string',
        'patient_id' => 'string',
        'last_dental_visit' => 'datetime',
    ];

    protected static function boot()
{
    parent::boot();

    static::creating(function ($medicalInfo) {
        // Ensure the patient_id is available
        if (!$medicalInfo->patient_id) {
            throw new \Exception('Cannot create medical information without a patient_id.');
        }

        // Remove potential dashes from patient_id for cleaner ID
        $cleanPatientId = str_replace('-', '', $medicalInfo->patient_id);


        // Random 4-digit suffix
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Final ID format: MED-{CleanedPatientID}-{Timestamp}-{Random}
        $medicalInfo->medical_info_id = "MED-{$cleanPatientId}-{$random}";
    });
}


    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

}