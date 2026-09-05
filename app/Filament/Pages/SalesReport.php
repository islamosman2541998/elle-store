<?php

namespace App\Filament\Pages;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.sales-report';

    protected static ?int $navigationSort = 1;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $status = null;

    public ?string $payment_status = null;

    public ?string $payment_method = null;

    public function mount(): void
    {
        $this->date_from = null;
        $this->date_to = null;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.reports_logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.sales_report');
    }

    public function getTitle(): string
    {
        return __('admin.sales_report');
    }

  public function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\DatePicker::make('date_from')
                ->label(__('admin.date_from'))
                ->native(false)
                ->live(),

            Forms\Components\DatePicker::make('date_to')
                ->label(__('admin.date_to'))
                ->native(false)
                ->live(),

            Forms\Components\Select::make('status')
                ->label(__('admin.order_status'))
                ->options([
                    'pending' => __('admin.order_status_pending'),
                    'confirmed' => __('admin.order_status_confirmed'),
                    'processing' => __('admin.order_status_processing'),
                    'shipped' => __('admin.order_status_shipped'),
                    'delivered' => __('admin.order_status_delivered'),
                    'cancelled' => __('admin.order_status_cancelled'),
                ])
                ->searchable()
                ->nullable()
                ->live(),

            Forms\Components\Select::make('payment_status')
                ->label(__('admin.payment_status'))
                ->options([
                    'unpaid' => __('admin.payment_status_unpaid'),
                    'paid' => __('admin.payment_status_paid'),
                    'partial' => __('admin.payment_status_partial'),
                    'refunded' => __('admin.payment_status_refunded'),
                ])
                ->searchable()
                ->nullable()
                ->live(),

            Forms\Components\Select::make('payment_method')
                ->label(__('admin.payment_method'))
                ->options([
                    'cash_on_delivery' => __('admin.payment_method_cash_on_delivery'),
                    'bank_transfer' => __('admin.payment_method_bank_transfer'),
                    'wallet_transfer' => __('admin.payment_method_wallet_transfer'),
                ])
                ->searchable()
                ->nullable()
                ->live(),
        ])
        ->columns(5);
}

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label(__('admin.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->exportCsv()),
        ];
    }

    public function getOrdersQuery()
    {
        return Order::query()
            ->when($this->date_from, function ($query) {
                $query->whereDate('created_at', '>=', $this->date_from);
            })
            ->when($this->date_to, function ($query) {
                $query->whereDate('created_at', '<=', $this->date_to);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->payment_status, function ($query) {
                $query->where('payment_status', $this->payment_status);
            })
            ->when($this->payment_method, function ($query) {
                $query->where('payment_method', $this->payment_method);
            });
    }

    public function getSummary(): array
    {
        $query = $this->getOrdersQuery();

        return [
            'orders_count' => (clone $query)->count(),
            'subtotal' => (float) (clone $query)->sum('subtotal'),
            'discount_total' => (float) (clone $query)->sum('discount_total'),
            'shipping_total' => (float) (clone $query)->sum('shipping_total'),
            'tax_total' => (float) (clone $query)->sum('tax_total'),
            'grand_total' => (float) (clone $query)->sum('grand_total'),
        ];
    }

    public function getLatestOrders()
    {
        return $this->getOrdersQuery()
            ->latest()
            ->limit(20)
            ->get();
    }

   public function exportCsv(): StreamedResponse
{
    $fileName = 'sales-report-' . now()->format('Y-m-d-H-i') . '.csv';

    $orders = $this->getOrdersQuery()
        ->latest()
        ->get();

    return response()->streamDownload(function () use ($orders) {
        $handle = fopen('php://output', 'w');

        // مهم جدًا عشان Excel يقرأ العربي صح
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Order Number',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Status',
            'Payment Status',
            'Payment Method',
            'Subtotal',
            'Discount',
            'Shipping',
            'Tax',
            'Grand Total',
            'Created At',
        ]);

        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_number,
                $order->customer_name,
                $order->customer_email,

                // مهم عشان Excel مايشيلش الصفر من أول رقم الموبايل
                $this->formatCsvText($order->customer_phone),

                __('admin.order_status_' . $order->status),
                __('admin.payment_status_' . $order->payment_status),
                __('admin.payment_method_' . $order->payment_method),

                $order->subtotal,
                $order->discount_total,
                $order->shipping_total,
                $order->tax_total,
                $order->grand_total,

                optional($order->created_at)->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($handle);
    }, $fileName, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}
protected function formatCsvText(?string $value): string
{
    if (! $value) {
        return '';
    }

    $value = trim($value);

    return '="' . str_replace('"', '""', $value) . '"';
}
}