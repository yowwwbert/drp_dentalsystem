<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient\DentalChart;
use App\Models\Patient\Teeth;
use App\Models\Patient\ToothSurface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'valid_id',
        'guardian_id',
        'remaining_balance',
    ];

    protected $casts = [
        'remaining_balance' => 'integer',
    ];

    protected static function booted()
    {
        static::created(function ($patient) {
            try {
                DB::transaction(function () use ($patient) {
                    // Simple chart ID format: CHART-{patient_id}
                    $chart = DentalChart::create([
                        'chart_id' => 'CHART-' . $patient->patient_id,
                        'patient_id' => $patient->patient_id,
                    ]);

                    // All tooth numbers including permanent and temporary teeth
                    $toothNumbers = array_merge(
                        // Upper temporary teeth
                        [55, 54, 53, 52, 51, 61, 62, 63, 64, 65],
                        // Upper permanent teeth
                        range(11, 18), range(21, 28),
                        // Lower permanent teeth
                        range(31, 38), range(41, 48),
                        // Lower temporary teeth
                        [85, 84, 83, 82, 81, 71, 72, 73, 74, 75]
                    );

                    // Anterior teeth use different surfaces than posterior teeth
                    $anteriorTeeth = [
                        11, 12, 13, 21, 22, 23, 31, 32, 33, 41, 42, 43,
                        51, 52, 53, 61, 62, 63, 71, 72, 73, 81, 82, 83
                    ];

                    foreach ($toothNumbers as $number) {
                        // Simple tooth ID format: TOOTH-{patient_id}-{tooth_number}
                        $toothId = 'TOOTH-' . $patient->patient_id . '-' . $number;
                        
                        $tooth = Teeth::create([
                            'tooth_id' => $toothId,
                            'chart_id' => $chart->chart_id,
                            'tooth_number' => (string)$number,
                            'tooth_notes' => null,
                            'tooth_status' => null,
                            'created_by' => $patient->patient_id,
                            'updated_by' => $patient->patient_id,
                        ]);

                        // Determine which surfaces to create based on tooth type
                        if (in_array($number, $anteriorTeeth)) {
                            // Anterior teeth (incisors and canines)
                            $surfaces = ['Mesial', 'Distal', 'Labial', 'Lingual', 'Incisal'];
                        } else {
                            // Posterior teeth (premolars and molars)
                            $surfaces = ['Mesial', 'Distal', 'Buccal', 'Lingual', 'Occlusal'];
                        }

                        foreach ($surfaces as $surfaceType) {
                            // Simple surface ID format: SURF-{patient_id}-{tooth_number}-{surface_type_initial}
                            $surfaceInitial = substr($surfaceType, 0, 1);
                            $surfaceId = 'SURF-' . $patient->patient_id . '-' . $number . '-' . $surfaceInitial;
                            
                            ToothSurface::create([
                                'surface_id' => $surfaceId,
                                'tooth_id' => $tooth->tooth_id,
                                'surface_type' => $surfaceType,
                                'surface_status' => null,
                                'created_by' => $patient->patient_id,
                                'updated_by' => $patient->patient_id,
                                'surface_notes' => null,
                            ]);
                        }
                    }

                    Log::info("Dental chart auto-created for patient: {$patient->patient_id}");
                }, 5);
            } catch (\Exception $e) {
                Log::error("Failed to auto-create dental chart for patient {$patient->patient_id}: {$e->getMessage()}");
                // Don't throw - we don't want patient creation to fail if chart creation fails
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id', 'user_id');
    }

    public function appointments()
    {
        return $this->hasMany('App\Models\Appointment\Appointment', 'patient_id', 'patient_id');
    }

    public function billings()
    {
        return $this->hasMany('App\Models\Billing\Billings', 'patient_id', 'patient_id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\Billing\Payments', 'patient_id', 'patient_id');
    }

    public function dentalCharts()
    {
        return $this->hasMany(\App\Models\Patient\DentalChart::class, 'patient_id', 'patient_id');
    }
}