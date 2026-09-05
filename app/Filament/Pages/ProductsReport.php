<?php

namespace App\Filament\Pages;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static string $view = 'filament.pages.products-report';

    protected static ?int $navigationSort = 2;

    public ?string $category_id = null;

    public ?string $brand_id = null;

    public ?string $stock_status = null;

    public ?string $product_status = null;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.reports_logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.products_report');
    }

    public function getTitle(): string
    {
        return __('admin.products_report');
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

                Forms\Components\Select::make('product_status')
                    ->label(__('admin.product_status'))
                    ->options([
                        'active' => __('admin.active'),
                        'inactive' => __('admin.inactive'),
                        'featured' => __('admin.featured'),
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

    public function getProductsQuery()
    {
        return Product::query()
            ->with([
                'transNow',
                'category.transNow',
                'brand.transNow',
            ])
            ->when($this->category_id, function ($query) {
                $query->where('category_id', $this->category_id);
            })
            ->when($this->brand_id, function ($query) {
                $query->where('brand_id', $this->brand_id);
            })
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
            })
            ->when($this->product_status, function ($query) {
                if ($this->product_status === 'active') {
                    $query->where('is_active', true);
                }

                if ($this->product_status === 'inactive') {
                    $query->where('is_active', false);
                }

                if ($this->product_status === 'featured') {
                    $query->where('is_featured', true);
                }
            });
    }

    public function getSummary(): array
    {
        $query = $this->getProductsQuery();

        return [
            'products_count' => (clone $query)->count(),

            'active_products_count' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'featured_products_count' => (clone $query)
                ->where('is_featured', true)
                ->count(),

            'low_stock_products_count' => (clone $query)
                ->where('manage_stock', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->where('stock_quantity', '>', 0)
                ->count(),

            'out_of_stock_products_count' => (clone $query)
                ->where('stock_quantity', '<=', 0)
                ->count(),

            'stock_total_quantity' => (int) (clone $query)
                ->sum('stock_quantity'),
        ];
    }

    public function getProducts()
    {
        return $this->getProductsQuery()
            ->orderByDesc('id')
            ->limit(30)
            ->get();
    }

    public function getSoldQuantityForProduct(int $productId): int
    {
        return (int) OrderItem::query()
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'products-report-' . now()->format('Y-m-d-H-i') . '.csv';

        $products = $this->getProductsQuery()
            ->orderByDesc('id')
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
                'Price',
                'Sale Price',
                'Stock Quantity',
                'Low Stock Alert',
                'Sold Quantity',
                'Active',
                'Featured',
                'Created At',
            ]);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->id,
                    $product->transNow?->name,
                    $this->formatCsvText($product->sku),
                    $product->category?->transNow?->name,
                    $product->brand?->transNow?->name,
                    $product->price,
                    $product->sale_price,
                    $product->stock_quantity,
                    $product->low_stock_alert,
                    $this->getSoldQuantityForProduct($product->id),
                    $product->is_active ? __('admin.yes') : __('admin.no'),
                    $product->is_featured ? __('admin.yes') : __('admin.no'),
                    optional($product->created_at)->format('Y-m-d H:i:s'),
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