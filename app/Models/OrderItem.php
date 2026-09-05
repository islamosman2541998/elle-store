<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'original_unit_price',
        'discount_amount',
        'discount_source',
        'flash_sale_item_id',
        'flash_sale_counted_at',
        'product_discount_id',
        'quantity',
        'unit_price',
        'subtotal',
        'snapshot',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'snapshot' => 'array',
        'original_unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'flash_sale_counted_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function flashSaleItem()
    {
        return $this->belongsTo(FlashSaleItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    public function returnRequestItems()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
    public function productDiscount()
{
    return $this->belongsTo(ProductDiscount::class);
}
}