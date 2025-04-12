<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class TestDateSimulation extends Command
{
    protected $signature = 'test:date-simulation {date} {command} {--args=* : Arguments to pass to the command}';
    protected $description = 'Simulate a specific date and run a command';

    public function handle()
    {
        $simulatedDate = $this->argument('date');
        $commandToRun = $this->argument('command');
        $args = $this->option('args');
        
        $this->info("Simulating date: {$simulatedDate}");
        
        // Simpan waktu asli
        $realNow = now();
        
        try {
            // Set waktu virtual
            Carbon::setTestNow(Carbon::parse($simulatedDate));
            $this->info("System time set to: " . now()->format('Y-m-d H:i:s'));
            
            // Format args jika ada
            $commandWithArgs = $commandToRun;
            if (!empty($args)) {
                $commandWithArgs .= ' ' . implode(' ', $args);
            }
            
            // Jalankan command
            $this->info("Running command: {$commandWithArgs}");
            $this->call($commandToRun, $this->parseArgs($args));
            
        } finally {
            // Reset waktu
            Carbon::setTestNow($realNow);
            $this->info("System time restored to: " . now()->format('Y-m-d H:i:s'));
        }
        
        return 0;
    }
    
    private function parseArgs(array $args)
    {
        $parsed = [];
        
        foreach ($args as $arg) {
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $parsed[$matches[1]] = $matches[2];
            } elseif (preg_match('/^--([^=]+)$/', $arg, $matches)) {
                $parsed[$matches[1]] = true;
            } else {
                $parsed[] = $arg;
            }
        }
        
        return $parsed;
    }
}