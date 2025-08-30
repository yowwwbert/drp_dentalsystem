<?php

namespace App\Models\Clinic;

use Illuminate\Database\Eloquent\Model;
use App\Models\Appointment\Appointment;
use App\Models\Users\Dentist;
use App\Models\Users\Staff;

class Branches extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'branches';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'branch_id';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'branch_name',
        'branch_address',
        'branch_contact',
        'branch_email',
        'branch_logo',
        'branch_map',
        'branch_facebook',
        'branch_instagram',
        'operating_days',
        'opening_time',
        'closing_time',
    ];

    protected $casts = [
        'branch_id' => 'string',
        'operating_days' => 'array'
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'branch_id', 'branch_id');
    }

    public function dentists()
    {
        return $this->belongsToMany(Dentist::class, 'user_branch', 'branch_id', 'user_id')
            ->where('user_type', 'dentist');
    }

    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'user_branch', 'branch_id', 'user_id')
            ->where('user_type', 'staff');
    }
}

