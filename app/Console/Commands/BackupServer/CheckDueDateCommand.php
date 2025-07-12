<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikSubscriptionManager;
use App\Models\Langganan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckDueDateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langganan:check-due-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for subscriptions past due date and suspend them in Mikrotik';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikSubscriptionManager $mikrotikManager)
    {
        $this->info('Starting due date check...');
        
        $now = Carbon::now();
        $this->info('Current date: ' . $now->format('Y-m-d'));
        
        // Find active subscriptions past due date
        $pastDueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<=', $now->format('Y-m-d'))
        ->where('user_status', 'Aktif')
        ->get();
            
        $this->info('Found ' . $pastDueSubscriptions->count() . ' past due subscriptions');
        
        $successCount = 0;
        
        foreach ($pastDueSubscriptions as $langganan) {
            $this->info('Processing subscription ID: ' . $langganan->id . ' for pelanggan ID: ' . $langganan->pelanggan_id);
            
            // Update status in database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            // Update in Mikrotik
            $result = $mikrotikManager->handleSubscriptionStatus($langganan, 'suspend');
            
            if ($result) {
                $successCount++;
                $this->info('Successfully suspended in Mikrotik: ' . $langganan->id_pelanggan);
            } else {
                $this->error('Failed to suspend in Mikrotik: ' . $langganan->id_pelanggan);
            }
        }
        
        $this->info('Suspension process complete. Success: ' . $successCount . ' / ' . $pastDueSubscriptions->count());
        
        return 0;
    }
}