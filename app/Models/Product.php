<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',        // JSON ডাটা অ্যারে হিসেবে আসবে
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    // রিলেশনশিপ
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // ভেরিয়েন্ট রিলেশন (One Product -> Many Variants)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews() {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    // Virtual Attribute for Avg Rating
    public function getAvgRatingAttribute() {
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    // Total Review Count
    public function getReviewCountAttribute() {
        return $this->reviews()->count();
    }
}
