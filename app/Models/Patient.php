<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'patient_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'guardian_id',
        'remaining_balance',
    ];

    protected $casts = [
        'remaining_balance' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id', 'user_id');
    }
}
