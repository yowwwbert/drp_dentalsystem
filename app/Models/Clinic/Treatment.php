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
}
