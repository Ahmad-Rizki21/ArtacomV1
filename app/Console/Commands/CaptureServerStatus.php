<?php

namespace App\Console\Commands;

use App\Models\ServerStatus;
use Illuminate\Console\Command;

class CaptureServerStatus extends Command
{
    protected $signature = 'server:capture-status';
    protected $description = 'Captures current server status metrics';

    public function handle()
    {
        // Mengambil data load dan CPU usage
        $topOutput = shell_exec('top -bn1 | head -n 5');
        
        // Mengambil data proses
        $processData = shell_exec('ps aux | wc -l');
        
        // Parsing data
        $loadData = $this->parseLoad($topOutput);
        $cpuData = $this->parseCpu($topOutput);
        $memoryData = $this->parseMemory($topOutput);
        $processCount = (int)trim($processData);
        $runningProcesses = $this->parseRunningProcesses($topOutput);
        $uptime = $this->parseUptime($topOutput);
        
        // Simpan ke database
        ServerStatus::create([
            'cpu_usage' => $cpuData['cpu_usage'],
            'memory_usage' => $memoryData['memory_usage'],
            'load_1m' => $loadData['load_1m'],
            'load_5m' => $loadData['load_5m'],
            'load_15m' => $loadData['load_15m'],
            'uptime' => $uptime,
            'process_count' => $processCount,
            'running_processes' => $runningProcesses,
            'raw_data' => [
                'top_output' => $topOutput,
            ],
            'snapshot_time' => now(),
        ]);
        
        $this->info('Server status captured successfully');
    }
    
    private function parseLoad($output)
    {
        preg_match('/load average: ([0-9.]+), ([0-9.]+), ([0-9.]+)/', $output, $matches);
        
        return [
            'load_1m' => $matches[1] ?? 0,
            'load_5m' => $matches[2] ?? 0,
            'load_15m' => $matches[3] ?? 0,
        ];
    }
    
    private function parseCpu($output)
    {
        preg_match('/Cpu\(s\): ([0-9.]+)% us/', $output, $matches);
        
        return [
            'cpu_usage' => $matches[1] ?? 0,
        ];
    }
    
    private function parseMemory($output)
    {
        preg_match('/Mem: +([0-9]+) total, +([0-9]+) used/', $output, $matches);
        
        if (isset($matches[1]) && isset($matches[2]) && $matches[1] > 0) {
            $usagePercent = ($matches[2] / $matches[1]) * 100;
        } else {
            $usagePercent = 0;
        }
        
        return [
            'memory_usage' => $usagePercent,
        ];
    }
    
    private function parseUptime($output)
    {
        preg_match('/up ([^,]+)/', $output, $matches);
        
        return $matches[1] ?? '';
    }
    
    private function parseRunningProcesses($output)
    {
        preg_match('/([0-9]+) running/', $output, $matches);
        
        return $matches[1] ?? 0;
    }
}