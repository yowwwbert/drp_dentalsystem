<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic\Branches;

class Dentist extends Model
{
    protected $table = 'dentists';
    protected $primaryKey = 'dentist_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'dentist_id',
        'dentist_type',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'dentist_id', 'user_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branches::class, 'user_branch', 'user_id', 'branch_id');
    }
}
