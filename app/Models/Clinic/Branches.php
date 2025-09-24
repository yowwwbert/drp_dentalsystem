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

    /**
     * Bootstrap the model and its events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($branch) {
            if (!$branch->branch_id) {
                $branchName = $branch->branch_name;
                $placeInitials = static::getPlaceInitials($branchName);
                $branch->branch_id = static::generateCustomId($branchName, $placeInitials);
            }
        });
    }

    /**
     * Get the initials of the place from the branch name.
     *
     * @param string $branchName
     * @return string
     */
    protected static function getPlaceInitials($branchName)
    {
        $branchName = trim(str_ireplace('DRP', '', $branchName)); // Remove "DRP" case-insensitive
        if (!empty($branchName)) {
            return strtoupper(substr($branchName, 0, 3)); // First 3 letters of the remaining part
        }
        return 'XXX'; // Default if no place is detected after DRP
    }

    /**
     * Generate a custom branch_id.
     *
     * @param string $branchName
     * @param string $placeInitials
     * @return string
     */
    protected static function generateCustomId($branchName, $placeInitials)
    {
        $baseId = 'DRP'; // Fixed base ID as "DRP"
        $customId = $baseId . '-' . $placeInitials;

        // Ensure uniqueness by checking existing IDs
        $counter = 1;
        $originalId = $customId;
        while (static::where('branch_id', $customId)->exists()) {
            $customId = $originalId . $counter;
            $counter++;
        }

        return $customId;
    }

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