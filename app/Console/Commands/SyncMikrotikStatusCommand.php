<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Support\Facades\Log;

class SyncMikrotikStatusCommand extends Command
{
    protected $signature = 'app:sync-mikrotik {pelanggan_id? : ID pelanggan (opsional)}';
    protected $description = 'Sinkronisasi status pelanggan dari database ke Mikrotik';

    public function handle(MikrotikSubscriptionManager $mikrotikManager)
    {
        $this->info('Starting Mikrotik status synchronization...');
        
        $pelangganId = $this->argument('pelanggan_id');
        
        if ($pelangganId) {
            $langganan = Langganan::with('pelanggan.dataTeknis')
                ->where('pelanggan_id', $pelangganId)
                ->first();
            
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
        } 
        
        $this->info("Fetching all subscriptions for synchronization...");
        
        // REVISI: Ambil SEMUA langganan beserta relasi yang dibutuhkan dalam satu query efisien
        $allSubscriptions = Langganan::with('pelanggan.dataTeknis')->get();
        
        $this->info("Found {$allSubscriptions->count()} total subscriptions to process.");
        
        $successCount = 0;
        $failCount = 0;
        
        $this->withProgressBar($allSubscriptions, function ($langganan) use ($mikrotikManager, &$successCount, &$failCount) {
            if ($mikrotikManager->syncMikrotikStatus($langganan)) {
                $successCount++;
            } else {
                $failCount++;
                Log::warning('Failed to sync status for pelanggan_id: ' . $langganan->pelanggan_id);
            }
        });
        
        $this->newLine(2);
        $this->info("Sync complete. Results:");
        $this->info("- Success: {$successCount}");
        $this->error("- Failed: {$failCount}");
        
        return 0;
    }
}
