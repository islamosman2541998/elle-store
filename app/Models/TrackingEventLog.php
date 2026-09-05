<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackingEventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_name',
        'event_id',
        'source',
        'platform',
        'status',
        'payload',
        'response',
        'ip_address',
        'user_agent',
        'order_id',
        'customer_id',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}