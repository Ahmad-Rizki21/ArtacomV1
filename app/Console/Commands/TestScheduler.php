<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestScheduler extends Command
{
    protected $signature = 'test:scheduler';
    protected $description = 'Test if scheduler is working';

    public function handle()
    {
        $this->info('Scheduler test running at: ' . now());
        Log::info('Scheduler test executed at ' . now());
        return 0;
    }
}