<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingCompany extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'phone',
        'email',
        'tracking_url_template',
        'integration_driver',
        'api_base_url',
        'api_key',
        'webhook_secret',
        'pickup_location_id',
        'auto_create_shipment',
        'sandbox_mode',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_create_shipment' => 'boolean',
        'sandbox_mode' => 'boolean',
        // Credentials are encrypted at rest.
        'api_key' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];

    public function usesApi(): bool
    {
        return \App\Services\Carriers\CarrierManager::isApiDriver($this->integration_driver);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function generateTrackingUrl(?string $trackingNumber): ?string
    {
        if (! $trackingNumber || ! $this->tracking_url_template) {
            return null;
        }

        return str_replace('{tracking_number}', $trackingNumber, $this->tracking_url_template);
    }
}