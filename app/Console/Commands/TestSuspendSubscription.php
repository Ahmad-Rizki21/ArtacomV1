<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class TestSuspendSubscription extends Command
{
    protected $signature = 'test:suspend {date=2025-04-26}';
    protected $description = 'Test suspend subscription on due date';

    public function handle()
    {
        $testDate = $this->argument('date');
        $this->info("Menguji suspend pada tanggal: {$testDate}");
        
        // Simpan waktu asli
        $realNow = now();
        
        try {
            // Set waktu virtual
            Carbon::setTestNow(Carbon::parse($testDate));
            $this->info("Waktu sistem diatur ke: " . now()->format('Y-m-d H:i:s'));
            
            // Jalankan command suspend
            $this->call('invoice:suspend-due');
            
        } finally {
            // Reset waktu
            Carbon::setTestNow($realNow);
            $this->info("Waktu sistem dikembalikan ke: " . now()->format('Y-m-d H:i:s'));
        }
        
        return 0;
    }
}