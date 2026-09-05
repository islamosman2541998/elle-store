<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'order_id',
        'shipping_company_id',
        'shipping_city_id',
        'status',
        'tracking_number',
        'tracking_url',
        'carrier_shipment_id',
        'carrier_status',
        'carrier_synced_at',
        'carrier_payload',
        'carrier_error',
        'shipping_fee',
        'shipping_address_snapshot',
        'assigned_at',
        'picked_up_at',
        'in_transit_at',
        'delivered_at',
        'failed_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'shipping_address_snapshot' => 'array',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'returned_at' => 'datetime',
        'carrier_synced_at' => 'datetime',
        'carrier_payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function company()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
    }

    public function city()
    {
        return $this->belongsTo(ShippingCity::class, 'shipping_city_id');
    }
}