<?php

namespace App\Services;

use App\Models\StoreSetting;

class PaymentSettingsService
{
    public function availablePaymentMethods(): array
    {
        $settings = StoreSetting::current();

        $methods = [];

        if ($settings->cash_on_delivery_enabled) {
            $methods['cash_on_delivery'] = __('admin.payment_cash_on_delivery');
        }

        if ($settings->bank_transfer_enabled) {
            $methods['bank_transfer'] = __('admin.payment_bank_transfer');
        }

        if ($settings->wallet_transfer_enabled) {
            $methods['wallet_transfer'] = __('admin.payment_wallet_transfer');
        }

        return $methods;
    }

    public function isPaymentMethodEnabled(?string $method): bool
    {
        if (! $method) {
            return false;
        }

        return array_key_exists($method, $this->availablePaymentMethods());
    }

    public function cashOnDeliveryFee(): float
    {
        return (float) StoreSetting::current()->cash_on_delivery_fee;
    }

    public function requiresPaymentProof(?string $method): bool
    {
        $settings = StoreSetting::current();

        if (! $settings->payment_proof_required) {
            return false;
        }

        return in_array($method, ['bank_transfer', 'wallet_transfer'], true);
    }
}