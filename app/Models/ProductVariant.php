<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_alert',
        'image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function translations()
    {
        return $this->hasMany(ProductVariantTranslation::class);
    }
    public function discounts()
{
    return $this->hasMany(ProductDiscount::class);
}

public function activeDiscounts()
{
    return $this->hasMany(ProductDiscount::class)
        ->where('is_active', true);
}

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->hasOne(ProductVariantTranslation::class)
            ->where('locale', $locale);
    }

    public function transNow()
    {
        return $this->hasOne(ProductVariantTranslation::class)
            ->where('locale', app()->getLocale());
    }

    public function arabicTranslation()
    {
        return $this->hasOne(ProductVariantTranslation::class)
            ->where('locale', 'ar');
    }

    public function englishTranslation()
    {
        return $this->hasOne(ProductVariantTranslation::class)
            ->where('locale', 'en');
    }
    public function attributeValues()
    {
        return $this->hasMany(ProductVariantAttributeValue::class);
    }

    public function values()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attribute_values')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    public function returnRequestItems()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class);
    }
    
}