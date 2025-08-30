<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;
use App\Models\Clinic\Branches;

class Staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'staff_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'staff_id',
        'position',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'staff_id', 'user_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branches::class, 'user_branch', 'user_id', 'branch_id');
    }
}
