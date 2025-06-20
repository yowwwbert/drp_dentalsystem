<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
