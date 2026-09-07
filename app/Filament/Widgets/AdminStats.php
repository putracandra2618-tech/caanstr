<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $completed = Order::query()->where('status', OrderStatus::Completed)->count();
        $paid = Order::query()->where('status', OrderStatus::Paid)->count();
        $pending = Order::query()->where('status', OrderStatus::Pending)->count();
        $failed = Order::query()->where('status', OrderStatus::Failed)->count();
        $totalRevenue = (float) Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
            ->sum('total');
        $totalOrders = Order::query()->count();
        $activeProducts = Product::query()->where('is_active', true)->count();
        $totalProducts = Product::query()->count();
        $bannedUsers = User::query()->where('is_banned', true)->count();
        $totalUsers = User::query()->count();

        $format = fn (int $number): string => number_format($number);

        return [
            Stat::make('Total Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.'))
                ->description("{$format($completed)} selesai · {$format($paid)} dibayar")
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Pesanan Selesai', $format($completed))
                ->description("{$format($paid)} dibayar menunggu top-up")
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Pesanan', $format($totalOrders))
                ->description("{$format($pending)} pending · {$format($failed)} gagal")
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('info'),
            Stat::make('Produk', $format($totalProducts))
                ->description("{$format($activeProducts)} aktif")
                ->descriptionIcon(Heroicon::OutlinedCube)
                ->color('primary'),
            Stat::make('Pengguna', $format($totalUsers))
                ->description("{$format($bannedUsers)} banned")
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('warning'),
        ];
    }
}
