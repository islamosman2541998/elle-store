<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FlashSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'product_variant_id',
        'discount_type',
        'discount_value',
        'quantity_limit',
        'sold_count',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->flashSale?->isRunning()) {
            return false;
        }

        if (
            $this->quantity_limit !== null
            && $this->sold_count >= $this->quantity_limit
        ) {
            return false;
        }

        return true;
    }

    public function calculateSalePrice(float $originalPrice): float
    {
        if (! $this->isAvailable()) {
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
}