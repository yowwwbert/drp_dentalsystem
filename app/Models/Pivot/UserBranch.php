<?php

namespace App\Models\Pivot;

use App\Models\Clinic\Branches;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

class UserBranch extends Model
{
    protected $table = 'user_branch';
    protected $fillable = ['user_id', 'branch_id'];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branches::class, 'branch_id', 'branch_id');
    }
}
