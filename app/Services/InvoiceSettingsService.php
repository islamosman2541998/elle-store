<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\StoreSetting;

class InvoiceSettingsService
{
    public function settings(): StoreSetting
    {
        return StoreSetting::current();
    }

    public function invoicesEnabled(): bool
    {
        return (bool) $this->settings()->invoices_enabled;
    }

    public function generateInvoiceNumber(?int $invoiceId = null): string
    {
        $settings = $this->settings();

        $prefix = $settings->invoice_prefix ?: 'INV';

        $id = $invoiceId ?: (Invoice::query()->max('id') + 1);

        $format = $settings->invoice_number_format ?: '{prefix}-{year}{month}{day}-{id}';

        return str_replace(
            ['{prefix}', '{year}', '{month}', '{day}', '{id}'],
            [
                $prefix,
                now()->format('Y'),
                now()->format('m'),
                now()->format('d'),
                str_pad((string) $id, 5, '0', STR_PAD_LEFT),
            ],
            $format
        );
    }

    public function sellerName(): ?string
    {
        return $this->settings()->invoice_seller_name;
    }

    public function invoiceAddress(): ?string
    {
        return $this->settings()->invoice_address;
    }

    public function invoiceNotes(): ?string
    {
        return $this->settings()->invoice_notes;
    }

    public function invoiceTerms(): ?string
    {
        return $this->settings()->invoice_terms;
    }

    public function taxNumber(): ?string
    {
        return $this->settings()->tax_number;
    }

    public function commercialRegistrationNumber(): ?string
    {
        return $this->settings()->commercial_registration_number;
    }

    public function shouldShowLogo(): bool
    {
        return (bool) $this->settings()->show_logo_on_invoice;
    }

    public function shouldShowTax(): bool
    {
        return (bool) $this->settings()->show_tax_on_invoice;
    }

    public function shouldShowPaymentStatus(): bool
    {
        return (bool) $this->settings()->show_payment_status_on_invoice;
    }
}