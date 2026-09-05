<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_address_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address_snapshot',
        'status',
        'payment_status',
        'payment_method',
        'payment_fee',
        'coupon_used_at',
        'subtotal',
        'discount_total',
        'shipping_total',
        'shipping_city_id',
        'shipping_zone_id',
        'shipping_discount_source',
        'free_shipping_offer_id',
        'tax_total',
        'grand_total',
        'coupon_code',
        'customer_notes',
        'admin_notes',
        'stock_deducted_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'shipping_address_snapshot' => 'array',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'coupon_used_at' => 'datetime',
        'payment_fee' => 'decimal:2',

    ];

    protected static function booted(): void
    {
        // Every later status change is logged by the dashboard actions, but the
        // order's opening status was not, which left the first row of the
        // history tab missing. Record it wherever the order comes from.
        static::created(function (self $order): void {
            $order->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => $order->status,
                'note' => __('admin.order_created'),
            ]);
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class);
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class)
            ->latestOfMany();
    }
    public function returnRequests()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    public function shippingCity()
{
    return $this->belongsTo(ShippingCity::class, 'shipping_city_id');
}

public function shippingZone()
{
    return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
}

public function freeShippingOffer()
{
    return $this->belongsTo(FreeShippingOffer::class);
}

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function latestShipment()
    {
        return $this->hasOne(Shipment::class)
            ->latestOfMany();
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)
            ->latest();
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)
            ->latestOfMany();
    }
}