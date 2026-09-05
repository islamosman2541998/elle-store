<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingCity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipping_zone_id',
        'name_ar',
        'name_en',
        'delivery_fee',
        'free_shipping_min_order',
        'estimated_delivery_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'free_shipping_min_order' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    public function calculateDeliveryFee(float $orderTotal): float
    {
        if (
            $this->free_shipping_min_order !== null
            && $orderTotal >= (float) $this->free_shipping_min_order
        ) {
            return 0;
        }

        return (float) $this->delivery_fee;
    }
    public function freeShippingOffers()
{
    return $this->hasMany(FreeShippingOffer::class);
}
}