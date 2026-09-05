<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductDiscount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'name_ar',
        'name_en',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'notes',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getNameAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    public function isRunning(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function calculateSalePrice(float $originalPrice): float
    {
        if (! $this->isRunning()) {
            return $originalPrice;
        }

        if ($this->discount_type === 'percentage') {
            $discount = $originalPrice * ((float) $this->discount_value / 100);

            return max(round($originalPrice - $discount, 2), 0);
        }

        return max(round($originalPrice - (float) $this->discount_value, 2), 0);
    }

    public function calculateDiscountAmount(float $originalPrice): float
    {
        return max(round($originalPrice - $this->calculateSalePrice($originalPrice), 2), 0);
    }

    public function scopeRunning($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}