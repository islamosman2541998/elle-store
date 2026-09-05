<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('admin.invoice') }} - {{ $invoice->invoice_number }}</title>

    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            background: #f5f5f5;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        .invoice-page {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .title {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }

        .section {
            margin-top: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }

        .label {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .value {
            font-weight: 600;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px;
            text-align: start;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
            font-weight: 700;
        }

        .totals {
            margin-top: 20px;
            margin-inline-start: auto;
            width: 320px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .grand {
            font-size: 18px;
            font-weight: 800;
        }

        .print-button {
            position: fixed;
            top: 20px;
            inset-inline-end: 20px;
            border: 0;
            background: #111827;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-page {
                max-width: 100%;
                border-radius: 0;
                padding: 20px;
            }

            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        {{ __('admin.print') }}
    </button>

    <div class="invoice-page">
        <div class="header">
            <div>
                <h1 class="title">{{ __('admin.invoice') }}</h1>
                <div class="muted">{{ $invoice->invoice_number }}</div>
                <div class="muted">
                    {{ __('admin.issued_at') }}:
                    {{ $invoice->issued_at?->format('Y-m-d H:i') ?? '-' }}
                </div>
            </div>

            <div>
                <div class="value">{{ $settings->sellerName() ?? config('app.name') }}</div>

                @if ($settings->invoiceAddress())
                    <div class="muted">{{ $settings->invoiceAddress() }}</div>
                @endif

                @if ($settings->taxNumber())
                    <div class="muted">
                        {{ __('admin.tax_number') }}: {{ $settings->taxNumber() }}
                    </div>
                @endif

                @if ($settings->commercialRegistrationNumber())
                    <div class="muted">
                        {{ __('admin.commercial_registration_number') }}:
                        {{ $settings->commercialRegistrationNumber() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">{{ __('admin.customer_information') }}</div>

            <div class="grid">
                <div class="box">
                    <div class="label">{{ __('admin.customer_name') }}</div>
                    <div class="value">{{ $invoice->customer_name }}</div>
                </div>

                <div class="box">
                    <div class="label">{{ __('admin.customer_phone') }}</div>
                    <div class="value">{{ $invoice->customer_phone ?? '-' }}</div>
                </div>

                <div class="box">
                    <div class="label">{{ __('admin.customer_email') }}</div>
                    <div class="value">{{ $invoice->customer_email ?? '-' }}</div>
                </div>

                @if ($settings->shouldShowPaymentStatus())
                    <div class="box">
                        <div class="label">{{ __('admin.payment_status') }}</div>
                        <div class="value">
                            {{ $invoice->order?->payment_status ? __('admin.payment_' . $invoice->order->payment_status) : '-' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">{{ __('admin.order_items') }}</div>

            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.product') }}</th>
                        <th>{{ __('admin.quantity') }}</th>
                        <th>{{ __('admin.unit_price') }}</th>
                        <th>{{ __('admin.subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->order?->items ?? [] as $item)
                        <tr>
                            <td>
                                {{ $item->product_name ?? $item->product?->name ?? '-' }}
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format((float) $item->unit_price, 2, '.', ',') }} EGP</td>
                            <td>{{ number_format((float) $item->subtotal, 2, '.', ',') }} EGP</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">{{ __('admin.no_records_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="totals">
                <div class="total-row">
                    <span>{{ __('admin.subtotal') }}</span>
                    <strong>{{ number_format((float) $invoice->subtotal, 2, '.', ',') }} EGP</strong>
                </div>

                <div class="total-row">
                    <span>{{ __('admin.discount_total') }}</span>
                    <strong>{{ number_format((float) $invoice->discount_total, 2, '.', ',') }} EGP</strong>
                </div>

                <div class="total-row">
                    <span>{{ __('admin.shipping_total') }}</span>
                    <strong>{{ number_format((float) $invoice->shipping_total, 2, '.', ',') }} EGP</strong>
                </div>

                @if ($settings->shouldShowTax())
                    <div class="total-row">
                        <span>{{ __('admin.tax_total') }}</span>
                        <strong>{{ number_format((float) $invoice->tax_total, 2, '.', ',') }} EGP</strong>
                    </div>
                @endif

                <div class="total-row grand">
                    <span>{{ __('admin.grand_total') }}</span>
                    <strong>{{ number_format((float) $invoice->grand_total, 2, '.', ',') }} EGP</strong>
                </div>
            </div>
        </div>

        @if ($settings->invoiceNotes() || $settings->invoiceTerms())
            <div class="section">
                @if ($settings->invoiceNotes())
                    <div class="box">
                        <div class="label">{{ __('admin.invoice_notes') }}</div>
                        <div>{{ $settings->invoiceNotes() }}</div>
                    </div>
                @endif

                @if ($settings->invoiceTerms())
                    <div class="box" style="margin-top: 12px;">
                        <div class="label">{{ __('admin.invoice_terms') }}</div>
                        <div>{{ $settings->invoiceTerms() }}</div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</body>
</html>