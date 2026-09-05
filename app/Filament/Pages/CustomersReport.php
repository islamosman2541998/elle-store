<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomersReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.pages.customers-report';

    protected static ?int $navigationSort = 3;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public ?string $customer_status = null;

    public ?string $has_orders = null;

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
        return __('admin.customers_report');
    }

    public function getTitle(): string
    {
        return __('admin.customers_report');
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

                Forms\Components\Select::make('customer_status')
                    ->label(__('admin.customer_status'))
                    ->options([
                        'active' => __('admin.active'),
                        'inactive' => __('admin.inactive'),
                    ])
                    ->searchable()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('has_orders')
                    ->label(__('admin.has_orders'))
                    ->options([
                        'yes' => __('admin.yes'),
                        'no' => __('admin.no'),
                    ])
                    ->searchable()
                    ->nullable()
                    ->live(),
            ])
            ->columns(4);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_csv')
                ->label(__('admin.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function getCustomersQuery()
    {
        return Customer::query()
            ->withCount('orders')
            ->when($this->date_from, function ($query) {
                $query->whereDate('created_at', '>=', $this->date_from);
            })
            ->when($this->date_to, function ($query) {
                $query->whereDate('created_at', '<=', $this->date_to);
            })
            ->when($this->customer_status, function ($query) {
                if ($this->customer_status === 'active') {
                    $query->where('is_active', true);
                }

                if ($this->customer_status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($this->has_orders, function ($query) {
                if ($this->has_orders === 'yes') {
                    $query->has('orders');
                }

                if ($this->has_orders === 'no') {
                    $query->doesntHave('orders');
                }
            });
    }

    public function getSummary(): array
    {
        $query = $this->getCustomersQuery();

        return [
            'customers_count' => (clone $query)->count(),

            'active_customers_count' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive_customers_count' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'customers_with_orders_count' => (clone $query)
                ->has('orders')
                ->count(),

            'customers_without_orders_count' => (clone $query)
                ->doesntHave('orders')
                ->count(),

            'total_customer_orders' => (int) Order::query()
                ->whereIn('customer_id', (clone $query)->pluck('id'))
                ->count(),
        ];
    }

    public function getCustomers()
    {
        return $this->getCustomersQuery()
            ->latest()
            ->limit(30)
            ->get();
    }

    public function getCustomerOrdersTotal(int $customerId): float
    {
        return (float) Order::query()
            ->where('customer_id', $customerId)
            ->sum('grand_total');
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'customers-report-' . now()->format('Y-m-d-H-i') . '.csv';

        $customers = $this->getCustomersQuery()
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Orders Count',
                'Orders Total',
                'Active',
                'Created At',
            ]);

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $this->formatCsvText($customer->phone),
                    $customer->orders_count,
                    $this->getCustomerOrdersTotal($customer->id),
                    $customer->is_active ? __('admin.yes') : __('admin.no'),
                    optional($customer->created_at)->format('Y-m-d H:i:s'),
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