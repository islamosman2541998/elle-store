<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

class TopSellingProductsChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return __('admin.top_selling_products');
    }

    protected function getData(): array
    {
        $items = OrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->with('product.transNow')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('admin.sold_quantity'),
                    'data' => $items->pluck('total_sold')->map(fn ($value) => (int) $value)->toArray(),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $items
                ->map(fn ($item) => $item->product?->transNow?->name ?? ('#' . $item->product_id))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}