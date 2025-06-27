<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $table = 'owners';
    protected $primaryKey = 'owner_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'owner_id',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'owner_id');
    }
}
