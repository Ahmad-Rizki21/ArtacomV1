<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Services\MikrotikSubscriptionManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncMikrotikStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-mikrotik {pelanggan_id? : ID pelanggan (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi status pelanggan dari database ke Mikrotik';

    /**
     * Execute the console command.
     */
    public function handle(MikrotikSubscriptionManager $mikrotikManager)
    {
        $this->info('Starting Mikrotik status synchronization...');
        
        $pelangganId = $this->argument('pelanggan_id');
        
        if ($pelangganId) {
            // Sync single subscription
            $langganan = Langganan::where('pelanggan_id', $pelangganId)->first();
            
            if (!$langganan) {
                $this->error("Langganan not found for pelanggan_id: {$pelangganId}");
                return 1;
            }
            
            $this->info("Syncing Mikrotik status for pelanggan_id: {$pelangganId}, current status: {$langganan->user_status}");
            
            $result = $mikrotikManager->syncMikrotikStatus($langganan);
            
            if ($result) {
                $this->info("Successfully synced status for pelanggan_id: {$pelangganId}");
            } else {
                $this->error("Failed to sync status for pelanggan_id: {$pelangganId}");
            }
            
            return $result ? 0 : 1;
        } else {
            // Sync all subscriptions
            $activeSubscriptions = Langganan::where('user_status', 'Aktif')->get();
            $suspendedSubscriptions = Langganan::where('user_status', 'Suspend')->get();
            
            $this->info("Found {$activeSubscriptions->count()} active subscriptions and {$suspendedSubscriptions->count()} suspended subscriptions");
            
            $successActive = 0;
            $successSuspended = 0;
            
            // Process Active subscriptions
            foreach ($activeSubscriptions as $langganan) {
                $this->info("Activating: {$langganan->pelanggan_id} ({$langganan->id_pelanggan})");
                if ($mikrotikManager->syncMikrotikStatus($langganan)) {
                    $successActive++;
                }
            }
            
            // Process Suspended subscriptions
            foreach ($suspendedSubscriptions as $langganan) {
                $this->info("Suspending: {$langganan->pelanggan_id} ({$langganan->id_pelanggan})");
                if ($mikrotikManager->syncMikrotikStatus($langganan)) {
                    $successSuspended++;
                }
            }
            
            $this->info("Sync complete. Results:");
            $this->info("- Active: {$successActive}/{$activeSubscriptions->count()} synchronized");
            $this->info("- Suspended: {$successSuspended}/{$suspendedSubscriptions->count()} synchronized");
            
            return 0;
        }
    }
}