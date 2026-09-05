<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'return_number',
        'order_id',
        'customer_id',
        'status',
        'reason',
        'refund_total',
        'customer_notes',
        'admin_notes',
'stock_restored_at',        'approved_at',
        'rejected_at',
        'received_at',
        'refunded_at',
    ];

    protected $casts = [
        'refund_total' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'received_at' => 'datetime',
        'refunded_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }
}