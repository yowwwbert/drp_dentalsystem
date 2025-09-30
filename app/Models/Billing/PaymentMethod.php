<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'payment_method_id';
    public $incrementing = false; // Since payment_method_id is varchar, not auto-incrementing
    protected $keyType = 'string';

    protected $fillable = [
        'payment_method_id',
        'payment_method_name',
        'payment_method_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship: One Payment Method can have many Payments
    public function payments()
    {
        return $this->hasMany(Payment::class, 'payment_method_id', 'payment_method_id');
    }

    /**
     * Boot the model and set up the custom ID generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->payment_method_id)) {
                // Derive type code from payment_method_type
                $typeCode = strtoupper(str_replace(' ', '_', $model->payment_method_type));
                // Find the last payment_method_id for this type
                $lastMethod = static::where('payment_method_id', 'like', "PM-{$typeCode}-%")
                    ->orderBy('payment_method_id', 'desc')
                    ->first();
                $nextNumber = $lastMethod ? (int) substr($lastMethod->payment_method_id, strlen("PM-{$typeCode}-")) + 1 : 1;
                $model->payment_method_id = "PM-{$typeCode}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}