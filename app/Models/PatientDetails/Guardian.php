<?php

namespace App\Models\PatientDetails;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $table = 'guardians';
    protected $primaryKey = 'guardian_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'guardian_id',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_relationship',
        'guardian_phone_number',
        'guardian_email_address',
        'guardian_valid_id'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Validate and set user_type if not provided
            // Generate timestamp (YYYYMMDDHHMMSS format, truncated for brevity if needed)
            $timestamp = date('YmdHis'); // e.g., 20250610124812

            // Generate random 4-digit number
            $randomDigits = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); // e.g., 0345

            // Combine to form user_id
            $user->guardian_id = "GUARD-{$timestamp}-{$randomDigits}";
        });
    }
}
