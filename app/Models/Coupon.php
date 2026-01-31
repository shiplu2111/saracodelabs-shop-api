<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Check if coupon is valid for a specific amount
     */
    public function isValid($totalAmount)
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->min_spend && $totalAmount < $this->min_spend) return false;

        return true;
    }
}
