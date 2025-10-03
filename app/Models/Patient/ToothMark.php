<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use App\Models\Users\User;
use App\Models\Patient\Teeth;
use App\Models\Patient\ToothSurface;
use App\Models\Patient\ToothTreatment;

class ToothMark extends Model
{
    protected $table = 'tooth_marks';
    protected $primaryKey = 'tooth_mark_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'tooth_mark_id',
        'mark_name',
        'mark_color',
        'created_by',
    ];

    /**
     * User who created this mark
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Teeth with this mark
     */
    public function teeth()
    {
        return $this->hasMany(Teeth::class, 'tooth_status', 'tooth_mark_id');
    }

    /**
     * Surfaces with this mark
     */
    public function surfaces()
    {
        return $this->hasMany(ToothSurface::class, 'surface_status', 'tooth_mark_id');
    }

    /**
     * Treatments with this mark
     */
    public function treatments()
    {
        return $this->hasMany(ToothTreatment::class, 'tooth_mark_id', 'tooth_mark_id');
    }
}
