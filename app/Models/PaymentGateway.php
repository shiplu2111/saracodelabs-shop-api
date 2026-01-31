<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; // 🔥 এই লাইনটি মিসিং ছিল

class PaymentGateway extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'credentials' => 'array',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
    ];

    /**
     * Get the Full URL for the Logo
     */
    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url('storage/' . $value) : null,
        );
    }
}
