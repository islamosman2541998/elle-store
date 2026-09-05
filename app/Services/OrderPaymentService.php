<?php

namespace App\Services;

use App\Models\Order;

class OrderPaymentService
{
    public function applyPaymentMethodToOrder(Order $order, ?string $paymentMethod): void
    {
        $paymentFee = 0;

        if ($paymentMethod === 'cash_on_delivery') {
            $paymentFee = app(PaymentSettingsService::class)->cashOnDeliveryFee();
        }

        $grandTotal = (float) $order->subtotal
            - (float) $order->discount_total
            + (float) $order->shipping_total
            + (float) $order->tax_total
            + (float) $paymentFee;

        $order->update([
            'payment_method' => $paymentMethod,
            'payment_fee' => $paymentFee,
            'grand_total' => max($grandTotal, 0),
        ]);
    }
}