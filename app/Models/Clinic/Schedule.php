<?php

namespace App\Models\Clinic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Schedule extends Model
{
    protected $table = 'schedules';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false; // Indicate that the primary key is not auto-incrementing
    protected $keyType = 'string'; // Specify the primary key type as string
    public $timestamps = false;

    protected $fillable = [
        'schedule_id',
        'branch_id',
        'schedule_date',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'schedule_id' => 'string',
        'branch_id' => 'string',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'schedule_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schedule) {
            // Ensure schedule_date is set
            if (!isset($schedule->schedule_date)) {
                Log::error("Schedule date not set for branch {$schedule->branch_id}");
                throw new \Exception('Schedule date is required for generating schedule_id.');
            }

            // Use schedule_date for the date part of the ID
            $date = $schedule->schedule_date instanceof \Carbon\Carbon
                ? $schedule->schedule_date->format('Ymd')
                : Carbon::parse($schedule->schedule_date)->format('Ymd');

            // Get first three characters of branch_id
            $branchPrefix = strtoupper(substr($schedule->branch_id, 0, 3));

            // Generate random 4 characters (alphanumeric)
            $attempts = 0;
            $maxAttempts = 10;

            do {
                $randomString = Str::random(4);
                $scheduleId = "{$branchPrefix}-{$date}-{$randomString}";

                // Check for uniqueness
                $exists = static::where('schedule_id', $scheduleId)->exists();
                $attempts++;
            } while ($exists && $attempts < $maxAttempts);

            if ($exists) {
                Log::error("Failed to generate unique schedule_id for branch {$schedule->branch_id} after {$maxAttempts} attempts.");
                throw new \Exception('Unable to generate unique schedule_id after maximum attempts.');
            }

            Log::debug("Generated schedule_id: {$scheduleId} for branch {$schedule->branch_id}");
            $schedule->schedule_id = $scheduleId;
        });
    }
}