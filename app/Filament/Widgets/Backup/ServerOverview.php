<?php

namespace App\Filament\Widgets;

use App\Models\ServerStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerOverview extends BaseWidget
{
    protected static ?int $sort = -4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $latestStatus = ServerStatus::latest('snapshot_time')->first();
        
        if (!$latestStatus) {
            return [
                Stat::make('CPU Usage', 'N/A')
                    ->description('No data available')
                    ->color('gray'),
                Stat::make('Memory Usage', 'N/A')
                    ->description('No data available')
                    ->color('gray'),
                Stat::make('System Load', 'N/A')
                    ->description('No data available')
                    ->color('gray'),
                Stat::make('Uptime', 'N/A')
                    ->description('No data available')
                    ->color('gray'),
            ];
        }
        
        $cpuColor = $this->getColorForMetric($latestStatus->cpu_usage, [20, 50, 80]);
        $memoryColor = $this->getColorForMetric($latestStatus->memory_usage, [50, 75, 90]);
        $loadColor = $this->getColorForMetric($latestStatus->load_1m, [1, 2, 4]);
        
        return [
            Stat::make('CPU Usage', number_format($latestStatus->cpu_usage, 2) . '%')
                ->description('Current CPU utilization')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($cpuColor)
                ->chart($this->getCpuChart()),
                
            Stat::make('Memory Usage', number_format($latestStatus->memory_usage, 2) . '%')
                ->description('RAM utilization')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color($memoryColor)
                ->chart($this->getMemoryChart()),
                
            Stat::make('System Load', number_format($latestStatus->load_1m, 2))
                ->description("{$latestStatus->process_count} processes, {$latestStatus->running_processes} running")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($loadColor),
                
            Stat::make('Uptime', $latestStatus->uptime)
                ->description('Last captured: ' . $latestStatus->snapshot_time->diffForHumans())
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),
        ];
    }
    
    private function getColorForMetric($value, $thresholds)
    {
        if ($value < $thresholds[0]) return 'success';
        if ($value < $thresholds[1]) return 'info';
        if ($value < $thresholds[2]) return 'warning';
        return 'danger';
    }
    
    private function getCpuChart()
    {
        return ServerStatus::latest('snapshot_time')
            ->limit(12)
            ->pluck('cpu_usage')
            ->toArray();
    }
    
    private function getMemoryChart()
    {
        return ServerStatus::latest('snapshot_time')
            ->limit(12)
            ->pluck('memory_usage')
            ->toArray();
    }
}