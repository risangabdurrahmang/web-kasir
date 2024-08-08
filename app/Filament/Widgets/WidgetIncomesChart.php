<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WidgetIncomesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Pemasukan';
    protected static string $color = 'success';

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? now();

        // Set start date to 12 months ago from the end date
        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::parse($endDate)->subMonths(12)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $data = Trend::model(Order::class)
            ->between(
                start: $startDate,
                end: $endDate,
            )
            ->perMonth()
            ->sum('total');

        // Fill missing months with 0
        $filledData = collect();
        $period = Carbon::parse($startDate)->monthsUntil($endDate);
        foreach ($period as $month) {
            $filledData->push([
                'date' => $month->format('M Y'),
                'aggregate' => $data->firstWhere('date', $month->format('Y-m'))?->aggregate ?? 0,
            ]);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $filledData->pluck('aggregate'),
                ],
            ],
            'labels' => $filledData->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
