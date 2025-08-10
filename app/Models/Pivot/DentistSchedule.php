<?php

namespace App\Models\Pivot;

use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic\Schedule;
use App\Models\Users\Dentist;

class DentistSchedule extends Model
{
    protected $table = 'dentist_schedule';
    protected $fillable = ['dentist_id', 'schedule_id'];


    public function dentist()
    {
        return $this->belongsTo(Dentist::class, 'dentist_id', ' dentist_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }
}
