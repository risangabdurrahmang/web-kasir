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
        $startDate = $this->filters['startDate'];
        $endDate = $this->filters['endDate'];

        $data = Trend::model(Order::class)
            ->between(
                start: $startDate ? Carbon::parse($startDate) : now()->subDay(7),
                end: $endDate ? Carbon::parse($endDate) : now(),
            )
            ->perDay()
            ->sum('total');

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $data->pluck('aggregate'),
                ],
            ],
            'labels' => $data->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('D');
                // $carbonDate = Carbon::parse($date);
                // return $carbonDate->isoFormat('dddd, D MMMM YYYY');
            }),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
