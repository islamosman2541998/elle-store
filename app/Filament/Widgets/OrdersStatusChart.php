<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersStatusChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return __('admin.orders_by_status');
    }

    protected function getData(): array
    {
        $statuses = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $labels = $statuses
            ->pluck('status')
            ->map(fn ($status) => __('admin.order_status_' . $status))
            ->toArray();

        $data = $statuses->pluck('total')->toArray();

        $backgroundColors = $statuses
            ->pluck('status')
            ->map(fn ($status) => $this->getStatusColor($status))
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => __('admin.orders'),
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '60%',
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',      // أصفر / برتقالي
            'confirmed' => '#3b82f6',    // أزرق
            'processing' => '#8b5cf6',   // بنفسجي
            'shipped' => '#06b6d4',      // سماوي
            'delivered' => '#22c55e',    // أخضر
            'cancelled' => '#ef4444',    // أحمر
            'returned' => '#f97316',     // برتقالي غامق
            'refunded' => '#64748b',     // رمادي
            default => '#a3a3a3',        // افتراضي
        };
    }
}