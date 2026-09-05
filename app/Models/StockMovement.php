<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'order_id',
        'order_item_id',
        'user_id',
        'type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reference',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public static function types(): array
{
    return [
        'in' => __('admin.stock_movement_type_in'),
        'out' => __('admin.stock_movement_type_out'),
        'manual_in' => __('admin.stock_movement_type_manual_in'),
        'manual_out' => __('admin.stock_movement_type_manual_out'),
        'order_deduction' => __('admin.stock_movement_type_order_deduction'),
        'return' => __('admin.stock_movement_type_return'),
        'adjustment' => __('admin.stock_movement_type_adjustment'),
    ];
}
}