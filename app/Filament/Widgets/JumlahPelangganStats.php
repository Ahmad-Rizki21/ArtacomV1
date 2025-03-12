<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class JumlahPelangganStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    // Mengatur tata letak kolom stat
    protected function getStatsColumns(): int
    {
        return 2; // 2 kolom untuk kedua stat
    }
    
    protected function getStats(): array
    {
        // Jumlah pelanggan Jakinet
        $jakinetCount = DB::table('langganan')
            ->where('id_brand', 'ajn-01')
            ->count();
            
        // Jumlah pelanggan Jelantik
        $jelantikCount = DB::table('langganan')
            ->where('id_brand', 'ajn-02')
            ->count();
            
        // Jumlah pelanggan Jelantik Nagrak
        $jelantikNagrakCount = DB::table('langganan')
            ->where('id_brand', 'ajn-03')
            ->count();
            
        // Total Jelantik (termasuk Nagrak)
        $totalJelantik = $jelantikCount + $jelantikNagrakCount;

        return [
            Stat::make('Jumlah Pelanggan Jakinet', $jakinetCount)
                ->description('Jumlah pelanggan Jakinet')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Jumlah Pelanggan Jelantik', $totalJelantik)
                ->description('Jumlah pelanggan Jelantik (termasuk Rusun Nagrak)')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
    
    public static function getSort(): int
    {
        return -4; // Prioritas tertinggi
    }
}