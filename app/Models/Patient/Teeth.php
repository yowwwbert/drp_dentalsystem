<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;

class Teeth extends Model
{
    protected $table = 'teeth'; // Explicitly set to 'teeth'
    protected $primaryKey = 'tooth_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'tooth_id',
        'chart_id',
        'tooth_number',
        'tooth_notes',
        'tooth_status',
        'created_by',
        'updated_by',
    ];

    public function toothMark()
    {
        return $this->hasOne(ToothMark::class, 'tooth_mark_id', 'tooth_mark_id');
    }

    public function treatments()
    {
        return $this->hasMany(ToothTreatment::class, 'tooth_id', 'tooth_id');
    }

    public function surfaces()
    {
        return $this->hasMany(ToothSurface::class, 'tooth_id', 'tooth_id');
    }

    public function dentalChart()
    {
        return $this->belongsTo(DentalChart::class, 'chart_id', 'chart_id');
    }
}