<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
