<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;

class CheckSubscriptionStatus extends Command
{
    protected $signature = 'subscriptions:check-status';
    protected $description = 'Check and update subscription statuses';

    public function handle()
    {
        $this->info('Memulai pengecekan status langganan...');
        
        $suspendedCount = Langganan::checkAllSubscriptionStatus();
        
        $this->info("Selesai. Jumlah langganan yang di-suspend: {$suspendedCount}");
        
        return 0;
    }
}