<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class JumlahPelangganStats extends BaseWidget
{
    // Polling interval untuk refresh data
    protected static ?string $pollingInterval = '60s';
    
    // Mengatur tata letak kolom stat dengan lebih fleksibel
    protected function getStatsColumns(): int
    {
        return 3; // 3 kolom untuk stat
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
                ->description('Total Pelanggan Jakinet')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart($this->generateTrendChart($jakinetCount))
                ->chartColor('success'),
                
            Stat::make('Jumlah Pelanggan Jelantik', $totalJelantik)
                ->description('Total Pelanggan Jelantik')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart($this->generateTrendChart($totalJelantik))
                ->chartColor('primary'),
                
            Stat::make('Pelanggan Jelantik Nagrak', $jelantikNagrakCount)
                ->description('Total Pelanggan Rusun Nagrak')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning')
                ->chart($this->generateTrendChart($jelantikNagrakCount))
                ->chartColor('warning')
        ];
    }
    
    /**
     * Generate trend chart dengan variasi lebih dinamis
     * 
     * @param int $baseValue
     * @return array
     */
    protected function generateTrendChart(int $baseValue): array
    {
        // Membuat trend chart dengan variasi yang lebih realistis
        return [
            $baseValue * 0.7,  // Titik awal yang lebih rendah
            $baseValue * 0.8,  // Sedikit peningkatan
            $baseValue * 0.9,  // Peningkatan lanjutan
            $baseValue * 0.95, // Mendekati nilai sebenarnya
            $baseValue         // Nilai aktual
        ];
    }
    
    /**
     * Mengatur prioritas widget
     * 
     * @return int
     */
    public static function getSort(): int
    {
        return -4; // Prioritas tertinggi
    }
}