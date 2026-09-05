<?php

namespace App\Filament\Pages;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static string $view = 'filament.pages.inventory-report';

    protected static ?int $navigationSort = 4;

    public ?string $category_id = null;

    public ?string $brand_id = null;

    public ?string $stock_status = null;

    public ?string $movement_type = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.reports_logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.inventory_report');
    }

    public function getTitle(): string
    {
        return __('admin.inventory_report');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label(__('admin.category'))
                    ->options(function () {
                        return Category::query()
                            ->with('transNow')
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($category) => [
                                $category->id => $category->transNow?->name ?? ('#' . $category->id),
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('brand_id')
                    ->label(__('admin.brand'))
                    ->options(function () {
                        return Brand::query()
                            ->with('transNow')
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn ($brand) => [
                                $brand->id => $brand->transNow?->name ?? ('#' . $brand->id),
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('stock_status')
                    ->label(__('admin.stock_status'))
                    ->options([
                        'in_stock' => __('admin.in_stock'),
                        'low_stock' => __('admin.low_stock'),
                        'out_of_stock' => __('admin.out_of_stock'),
                    ])
                    ->searchable()
                    ->nullable()
                    ->live(),

                Forms\Components\Select::make('movement_type')
                    ->label(__('admin.movement_type'))
                    ->options(fn () => StockMovement::types())
                    ->searchable()
                    ->nullable()
                    ->live(),

                Forms\Components\DatePicker::make('date_from')
                    ->label(__('admin.date_from'))
                    ->native(false)
                    ->live(),

                Forms\Components\DatePicker::make('date_to')
                    ->label(__('admin.date_to'))
                    ->native(false)
                    ->live(),
            ])
            ->columns(6);
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

    public function getProductsQuery()
    {
        return Product::query()
            ->with([
                'transNow',
                'category.transNow',
                'brand.transNow',
            ])
            ->when($this->category_id, fn ($query) => $query->where('category_id', $this->category_id))
            ->when($this->brand_id, fn ($query) => $query->where('brand_id', $this->brand_id))
            ->when($this->stock_status, function ($query) {
                if ($this->stock_status === 'in_stock') {
                    $query->where('stock_quantity', '>', 0);
                }

                if ($this->stock_status === 'low_stock') {
                    $query->where('manage_stock', true)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                        ->where('stock_quantity', '>', 0);
                }

                if ($this->stock_status === 'out_of_stock') {
                    $query->where('stock_quantity', '<=', 0);
                }
            });
    }

    public function getMovementsQuery()
    {
        return StockMovement::query()
            ->with([
                'product.transNow',
                'product.category.transNow',
                'product.brand.transNow',
            ])
            ->when($this->movement_type, fn ($query) => $query->where('type', $this->movement_type))
            ->when($this->date_from, fn ($query) => $query->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to, fn ($query) => $query->whereDate('created_at', '<=', $this->date_to))
            ->when($this->category_id, function ($query) {
                $query->whereHas('product', fn ($q) => $q->where('category_id', $this->category_id));
            })
            ->when($this->brand_id, function ($query) {
                $query->whereHas('product', fn ($q) => $q->where('brand_id', $this->brand_id));
            });
    }

    public function getSummary(): array
    {
        $productsQuery = $this->getProductsQuery();

        return [
            'products_count' => (clone $productsQuery)->count(),

            'stock_total_quantity' => (int) (clone $productsQuery)->sum('stock_quantity'),

            'low_stock_products_count' => (clone $productsQuery)
                ->where('manage_stock', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->where('stock_quantity', '>', 0)
                ->count(),

            'out_of_stock_products_count' => (clone $productsQuery)
                ->where('stock_quantity', '<=', 0)
                ->count(),

            'stock_movements_count' => (clone $this->getMovementsQuery())->count(),

            'stock_in_total' => (int) (clone $this->getMovementsQuery())
                ->whereIn('type', ['in', 'return', 'manual_in'])
                ->sum('quantity'),

            'stock_out_total' => (int) (clone $this->getMovementsQuery())
                ->whereIn('type', ['out', 'order_deduction', 'manual_out'])
                ->sum('quantity'),
        ];
    }

    public function getProducts()
    {
        return $this->getProductsQuery()
            ->orderBy('stock_quantity')
            ->limit(30)
            ->get();
    }

    public function getLatestMovements()
    {
        return $this->getMovementsQuery()
            ->latest()
            ->limit(30)
            ->get();
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'inventory-report-' . now()->format('Y-m-d-H-i') . '.csv';

        $products = $this->getProductsQuery()
            ->orderBy('stock_quantity')
            ->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Product Name',
                'SKU',
                'Category',
                'Brand',
                'Manage Stock',
                'Stock Quantity',
                'Low Stock Alert',
                'Stock Status',
                'Active',
                'Created At',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->transNow?->name,
                    $this->formatCsvText($product->sku),
                    $product->category?->transNow?->name,
                    $product->brand?->transNow?->name,
                    $product->manage_stock ? __('admin.yes') : __('admin.no'),
                    $product->stock_quantity,
                    $product->low_stock_alert,
                    $this->getProductStockStatusLabel($product),
                    $product->is_active ? __('admin.yes') : __('admin.no'),
                    optional($product->created_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function getProductStockStatusLabel(Product $product): string
    {
        if ((int) $product->stock_quantity <= 0) {
            return __('admin.out_of_stock');
        }

        if (
            $product->manage_stock
            && (int) $product->stock_quantity <= (int) $product->low_stock_alert
        ) {
            return __('admin.low_stock');
        }

        return __('admin.in_stock');
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