<?php

namespace App\Models\Clinic;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $table = 'treatments'; // Assuming the table name is 'treatments'
    protected $primaryKey = 'treatment_id'; // Assuming the primary key is 't
    public $incrementing = false; // If treatment_id is not an auto-incrementing field
    protected $keyType = 'string'; // Assuming treatment_id is a string type


    protected $fillable = [
        'treatment_id',
        'treatment_name',
        'treatment_type',
        'treatment_duration',
        'treatment_description',
        'treatment_cost',
        'is_active',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->treatment_id)) {
                do {
                    $randomNumber = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $model->treatment_id = 'TREAT-' . $randomNumber;
                } while (static::where('treatment_id', $model->treatment_id)->exists());
            }
        });
    }

    public function appointments(){
        return $this->belongsToMany('App\Models\Appointment\Appointments', 'treatment_id', 'appointment_id');
    }
}
