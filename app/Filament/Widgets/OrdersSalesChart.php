<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersSalesChart extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return __('admin.sales_last_7_days');
    }

    protected function getData(): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        $sales = Order::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('SUM(grand_total) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $period = CarbonPeriod::create($startDate, $endDate);

        $labels = [];
        $data = [];

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');

            $labels[] = Carbon::parse($dateKey)->format('d M');
            $data[] = (float) ($sales[$dateKey]->total ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('admin.sales'),
                    'data' => $data,
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}