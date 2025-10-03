<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;

class ToothSurface extends Model
{
    protected $primaryKey = 'surface_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'surface_id',
        'tooth_id',
        'surface_type',
        'surface_status',
        'created_by',
        'updated_by',
        'surface_notes',
    ];

    public function tooth()
    {
        return $this->belongsTo(App\Model\Patient\Teeth::class, 'tooth_id', 'tooth_id');
    }

    public function toothMark()
    {
        return $this->belongsTo(ToothMark::class, 'surface_status', 'tooth_mark_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    public function treatments()
    {
        return $this->hasMany(ToothTreatment::class, 'surface_id', 'surface_id');
    }
}