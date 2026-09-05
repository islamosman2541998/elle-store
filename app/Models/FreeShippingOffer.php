<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FreeShippingOffer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name_ar',
        'name_en',
        'minimum_order_amount',
        'shipping_city_id',
        'shipping_zone_id',
        'starts_at',
        'ends_at',
        'is_active',
        'priority',
        'notes',
    ];

    protected $casts = [
        'minimum_order_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function city()
    {
        return $this->belongsTo(ShippingCity::class, 'shipping_city_id');
    }

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function getNameAttribute(): string
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

    public function isValidForOrder(float $orderTotal, ?int $shippingCityId = null, ?int $shippingZoneId = null): bool
    {
        if (! $this->isRunning()) {
            return false;
        }

        if (
            $this->minimum_order_amount !== null
            && $orderTotal < (float) $this->minimum_order_amount
        ) {
            return false;
        }

        if (
            $this->shipping_city_id !== null
            && $shippingCityId !== null
            && (int) $this->shipping_city_id !== (int) $shippingCityId
        ) {
            return false;
        }

        if (
            $this->shipping_zone_id !== null
            && $shippingZoneId !== null
            && (int) $this->shipping_zone_id !== (int) $shippingZoneId
        ) {
            return false;
        }

        return true;
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