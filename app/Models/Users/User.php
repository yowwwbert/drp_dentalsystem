<?php

namespace App\Models\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Models\Clinic\Branches;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'suffix',
        'age',
        'birth_date',
        'religion',
        'sex',
        'occupation',
        'email_address',
        'phone_number',
        'address',
        'user_type',
        'status',
        'profile_picture',
        'password',
        'remember_token',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Validate and set user_type if not provided
            $validUserTypes = ['Patient', 'Dentist', 'Staff', 'Owner'];
            $user->user_type = in_array($user->user_type, $validUserTypes) ? $user->user_type : 'Patient';

            // Define prefix based on user_type
            $prefixes = [
                'Patient' => 'PAT',
                'Dentist' => 'DEN',
                'Staff' => 'STA',
                'Owner' => 'OWN',
            ];

            $prefix = $prefixes[$user->user_type] ?? 'PAT';

            // Generate timestamp (YYYYMMDDHHMMSS format, truncated for brevity if needed)
            $timestamp = date('YmdHis'); // e.g., 20250610124812

            // Generate random 4-digit number
            $randomDigits = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT); // e.g., 0345

            // Combine to form user_id
            $user->user_id = "{$prefix}-{$timestamp}-{$randomDigits}";
        });
    }

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Mark the user's phone number as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => now(),
        ])->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

        public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function dentist()
    {
        return $this->hasOne(Dentist::class);
    }

    public function owner()
    {
        return $this->hasOne(Owner::class);
    }
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branches::class, 'user_branch', 'user_id', 'branch_id', 'user_id', 'branch_id');
    }

    public function getEmailForPasswordReset()
{
    return $this->email_address;
}

    public function billing()
    {
        return $this->hasMany(Billings::class, 'patient_id', 'user_id');
    }

public function sendPhoneVerificationNotification()
    {
        $phone = $this->user_type === 'Patient' && $this->age < 18 && $this->guardian_phone_number
            ? $this->guardian_phone_number
            : $this->phone_number;

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP in cache with 10-minute expiration
        Cache::put('phone_verification_' . $this->user_id, $otp, now()->addMinutes(10));

        // Log OTP instead of sending via SMS
        Log::info('OTP generated for phone verification', [
            'otp' => $otp,
        ]);
    }


}