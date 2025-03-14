<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use App\Models\MikrotikServer;

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

        return [
            Stat::make('Jumlah Pelanggan Jakinet', $jakinetCount)
                ->description('Jumlah pelanggan Jakinet')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
                
            Stat::make('Jumlah Pelanggan Jelantik', $totalJelantik)
                ->description('Jumlah pelanggan Jelantik (termasuk Rusun Nagrak)')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Mikrotik Servers', "$onlineServers / $totalMikrotikServers")
                ->description('Online / Total Servers')
                ->descriptionIcon('heroicon-m-server')
                ->color($offlineServers > 0 ? 'danger' : 'success')
                ->chart(
                    // Buat chart sederhana untuk representasi visual
                    $this->generateServerStatusChart($onlineServers, $totalMikrotikServers)
                )
        ];
    }
    
    /**
     * Generate chart sederhana untuk status server
     * 
     * @param int $onlineServers
     * @param int $totalServers
     * @return array
     */
    protected function generateServerStatusChart(int $onlineServers, int $totalServers): array
    {
        // Buat representasi chart sederhana
        $percentOnline = $totalServers > 0 ? ($onlineServers / $totalServers) * 100 : 0;
        
        return array_map(function() use ($percentOnline) {
            return $percentOnline;
        }, range(1, 10));
    }
    
    public static function getSort(): int
    {
        return -4; // Prioritas tertinggi
    }
}