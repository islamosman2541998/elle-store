<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'free_shipping',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'free_shipping' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getNameAttribute(): ?string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    public function isValidForAmount(float $amount): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if (
            $this->minimum_order_amount !== null
            && $amount < (float) $this->minimum_order_amount
        ) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (! $this->isValidForAmount($amount)) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = $amount * ((float) $this->value / 100);

            if ($this->maximum_discount_amount !== null) {
                $discount = min($discount, (float) $this->maximum_discount_amount);
            }

            return round($discount, 2);
        }

        return min((float) $this->value, $amount);
    }
}