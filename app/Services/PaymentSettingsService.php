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

        if ($settings->instapay_enabled) {
            $methods['instapay'] = __('admin.payment_instapay');
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
        // InstaPay is paid before the order is placed and leaves no other
        // trace for the shop, so the receipt is always required - the global
        // switch does not turn it off.
        if ($method === 'instapay') {
            return true;
        }

        if (! StoreSetting::current()->payment_proof_required) {
            return false;
        }

        return in_array($method, ['bank_transfer', 'wallet_transfer'], true);
    }
}