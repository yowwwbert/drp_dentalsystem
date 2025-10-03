<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users\Patient;
use App\Models\Patient\Teeth;


class DentalChart extends Model
{
    protected $primaryKey = 'chart_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'chart_id',
        'patient_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function teeth()
    {
        return $this->hasMany(Teeth::class, 'chart_id', 'chart_id');
    }
}