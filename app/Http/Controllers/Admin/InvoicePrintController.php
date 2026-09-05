<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceSettingsService;

class InvoicePrintController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoice->loadMissing([
            'order.items.product',
            'order.items.variant',
            'customer',
        ]);

        return view('admin.invoices.print', [
            'invoice' => $invoice,
            'settings' => app(InvoiceSettingsService::class),
        ]);
    }
}