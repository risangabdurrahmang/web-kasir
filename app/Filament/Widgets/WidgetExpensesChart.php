<?php

namespace App\Filament\Widgets;

use App\Models\Expenses;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class WidgetExpensesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Expenses';
    protected static string $color = 'danger';

    protected function getData(): array
    {
        $startDate = $this->filters['startDate'];
        $endDate = $this->filters['endDate'];

        $data = Trend::model(Expenses::class)
            ->between(
                start: $startDate ? Carbon::parse($startDate) : now()->subDay(7),
                end: $endDate ? Carbon::parse($endDate) : now(),
            )
            ->perDay()
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Expenses',
                    'data' => $data->pluck('aggregate'),
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('D');
            }),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
