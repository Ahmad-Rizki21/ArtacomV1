<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use App\Models\MikrotikServer;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class JumlahPelangganStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    // Mengatur tata letak kolom stat
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

        // Statistik Mikrotik Server
        $totalMikrotikServers = MikrotikServer::count();
        $onlineServers = MikrotikServer::where('last_connection_status', 'success')->count();
        $offlineServers = $totalMikrotikServers - $onlineServers;

        // Data untuk chart pelanggan
        $chartData = [
            'Jakinet' => $jakinetCount,
            'Jelantik' => $jelantikCount,
            'Jelantik Nagrak' => $jelantikNagrakCount,
        ];

        return [
            Stat::make('Jumlah Pelanggan Jakinet', $jakinetCount)
                ->description('Jumlah pelanggan Jakinet')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart($this->generateBrandChart([$jakinetCount, $jakinetCount*0.9, $jakinetCount*0.95, $jakinetCount*0.97, $jakinetCount]))
                ->chartColor('success'),
                
            Stat::make('Jumlah Pelanggan Jelantik', $totalJelantik)
                ->description('Jumlah pelanggan Jelantik (termasuk Rusun Nagrak)')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart($this->generateBrandChart([$totalJelantik*0.8, $totalJelantik*0.85, $totalJelantik*0.9, $totalJelantik*0.95, $totalJelantik]))
                ->chartColor('primary'),
                
            Stat::make('Mikrotik Servers', "$onlineServers / $totalMikrotikServers")
                ->description('Online / Total Servers')
                ->descriptionIcon('heroicon-m-server')
                ->color($offlineServers > 0 ? 'danger' : 'success')
                ->chart($this->generateServerStatusDonutChart($onlineServers, $offlineServers))
                ->chartColor($offlineServers > 0 ? 'danger' : 'success')
        ];
    }
    
    /**
     * Generate chart brand pelanggan
     * 
     * @param array $data
     * @return array
     */
    protected function generateBrandChart(array $data): array
    {
        return $data;
    }
    
    /**
     * Generate donut chart untuk status server menggunakan format ApexCharts
     * 
     * @param int $onlineServers
     * @param int $offlineServers
     * @return array
     */
    protected function generateServerStatusDonutChart(int $onlineServers, int $offlineServers): array
    {
        // Format ini sesuai dengan render preview chart di Stat
        return [
            $offlineServers, $onlineServers, $onlineServers, $onlineServers, $onlineServers,
            $onlineServers, $onlineServers, $onlineServers, $onlineServers, $onlineServers
        ];
    }
    
    public static function getSort(): int
    {
        return -4; // Prioritas tertinggi
    }
}