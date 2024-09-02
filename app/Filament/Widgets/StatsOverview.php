<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Expenses;
use App\Models\Order;
use Awcodes\Overlook\Concerns\HandlesOverlookWidgetCustomization;
use Awcodes\Overlook\OverlookPlugin;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
            Carbon::parse($this->filters['startDate']) :
            Carbon::now()->subDay(7)->startOfDay();

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate']) :
            now();

        $pemasukan = Order::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $pengeluaran = Expenses::whereBetween('date_expense', [$startDate, $endDate])->sum('amount');
        $selisih = $pemasukan - $pengeluaran;

        return [
            Stat::make('Pemasukan', 'Rp. ' . number_format($pemasukan, 0, ',', '.')),
            Stat::make('Pengeluaran', 'Rp. ' . number_format($pengeluaran, 0, ',', '.')),
            Stat::make('Selisih', 'Rp. ' . number_format($selisih, 0, ',', '.')),
        ];
    }
}
