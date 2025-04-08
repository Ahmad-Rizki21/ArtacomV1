<?php

namespace App\Filament\Widgets;

use App\Models\MikrotikServer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MikrotikServerStatus extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    
    protected static ?int $sort = -4;
    
    protected function getStats(): array
    {
        $totalServers = MikrotikServer::count();
        $onlineServers = MikrotikServer::where('last_connection_status', 'success')->count();
        $offlineServers = MikrotikServer::where('last_connection_status', 'failed')->count();
        
        return [
            Stat::make('Total Servers', $totalServers)
                ->description('Total Mikrotik servers')
                ->icon('heroicon-o-server'),
                
            Stat::make('Online Servers', $onlineServers)
                ->description('Servers currently online')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
                
            Stat::make('Offline Servers', $offlineServers)
                ->description('Servers currently offline')
                ->color('danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}