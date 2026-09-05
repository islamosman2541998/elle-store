<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentMethodDisplay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'image',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? $this->name_ar
            : $this->name_en;
    }

    public static function defaultKeys(): array
    {
        return [
            'cash_on_delivery' => 'Cash On Delivery',
            'bank_transfer' => 'Bank Transfer',
            'wallet_transfer' => 'Wallet Transfer',
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'meeza' => 'Meeza',
            'paypal' => 'PayPal',
        ];
    }
}