<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Expenses;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;

        $pemasukan = Order::query()
            ->when($startDate, fn(Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn(Builder $query) => $query->whereDate('created_at', '<=', $endDate))
            ->sum('total');
        $pengeluaran = Expenses::query()
            ->when($startDate, fn(Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn(Builder $query) => $query->whereDate('created_at', '<=', $endDate))
            ->sum('amount');
        $pelanggan = Customer::query()
            ->when($startDate, fn(Builder $query) => $query->whereDate('created_at', '>=', $startDate))
            ->when($endDate, fn(Builder $query) => $query->whereDate('created_at', '<=', $endDate))
            ->count();

        return [
            Stat::make(
                label: 'Pemasukan',
                value: 'Rp. ' . number_format($pemasukan, 0, ',', '.'),
            ),
            // ->description('32k increase')
            // ->descriptionIcon('heroicon-m-arrow-trending-down')
            // ->chart([7, 2, 10, 3, 15, 4, 17])
            // ->color('success'),
            Stat::make(
                label: 'Pengeluaran',
                value: 'Rp. ' . number_format($pengeluaran, 0, ',', '.'),
            ),
            Stat::make(
                label: 'Pelanggan',
                value: $pelanggan,
            ),
        ];
    }
}
