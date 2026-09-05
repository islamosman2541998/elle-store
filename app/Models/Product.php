<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'sku',
        'barcode',
        'main_image',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'low_stock_alert',
        'manage_stock',
        'allow_backorder',
        'weight',
        'is_active',
        'is_featured',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'manage_stock' => 'boolean',
        'allow_backorder' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function translation($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $this->hasOne(ProductTranslation::class)
            ->where('locale', $locale);
    }

    public function transNow()
    {
        return $this->hasOne(ProductTranslation::class)
            ->where('locale', app()->getLocale());
    }

    public function arabicTranslation()
    {
        return $this->hasOne(ProductTranslation::class)
            ->where('locale', 'ar');
    }

    public function englishTranslation()
    {
        return $this->hasOne(ProductTranslation::class)
            ->where('locale', 'en');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)
            ->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('sort_order');
    }
    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class)
            ->orderBy('sort_order');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
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
public function discounts()
{
    return $this->hasMany(ProductDiscount::class);
}

public function activeDiscounts()
{
    return $this->hasMany(ProductDiscount::class)
        ->where('is_active', true);
}
}