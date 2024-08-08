<?php

namespace App\Filament\Widgets;

use App\Models\Expenses;
use App\Models\Order;
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
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? now();

        $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfYear()->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $pemasukan = Order::whereBetween('order_date', [$startDate, $endDate])->sum('total');
        $pengeluaran = Expenses::whereBetween('date_expense', [$startDate, $endDate])->sum('amount');
        $selisih = $pemasukan - $pengeluaran;

        return [
            Stat::make('Pemasukan', 'Rp. ' . number_format($pemasukan, 0, ',', '.')),
            // ->description('32k increase')
            // ->descriptionIcon('heroicon-m-arrow-trending-down')
            // ->color('success'),
            Stat::make('Pengeluaran', 'Rp. ' . number_format($pengeluaran, 0, ',', '.')),
            // ->description('7% increase')
            // ->descriptionIcon('heroicon-m-arrow-trending-up')
            // ->color('danger'),
            Stat::make('Selisih', 'Rp. ' . number_format($selisih, 0, ',', '.')),
            // ->description('3% increase')
            // ->descriptionIcon('heroicon-m-arrows-up-down')
            // ->color('info'),
        ];
    }
}
