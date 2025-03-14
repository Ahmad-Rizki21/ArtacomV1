<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Services\MikrotikConnectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckOverdueSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-overdue-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue subscriptions and suspend them in Mikrotik';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue subscription check...');
        
        $now = Carbon::now();
        $this->info('Current date: ' . $now->format('Y-m-d'));
        
        // Find active subscriptions past due date
        $pastDueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<', $now->format('Y-m-d'))
            ->where('user_status', 'Aktif')
            ->get();
            
        $this->info('Found ' . $pastDueSubscriptions->count() . ' overdue subscriptions');
        
        if ($pastDueSubscriptions->count() == 0) {
            $this->info('No overdue subscriptions found. Exiting.');
            return 0;
        }
        
        $mikrotikService = new MikrotikConnectionService();
        $successCount = 0;
        
        foreach ($pastDueSubscriptions as $langganan) {
            $this->info('Processing subscription ID: ' . $langganan->id . ' for pelanggan ID: ' . $langganan->pelanggan_id);
            
            // Simpan status lama untuk log
            $oldStatus = $langganan->user_status;
            
            // Update status in database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            // Update in Mikrotik jika ID Pelanggan tersedia
            if (!empty($langganan->id_pelanggan)) {
                try {
                    // Update profile di Mikrotik ke suspended (lowercase sesuai standar tim IT)
                    $result = $mikrotikService->updatePppoeProfile($langganan->id_pelanggan, 'suspended');
                    
                    if ($result) {
                        $successCount++;
                        $this->info('Successfully suspended in Mikrotik: ' . $langganan->id_pelanggan);
                    } else {
                        $this->error('Failed to suspend in Mikrotik: ' . $langganan->id_pelanggan);
                    }
                } catch (\Exception $e) {
                    $this->error('Error updating Mikrotik: ' . $e->getMessage());
                    Log::error('Error updating Mikrotik profile', [
                        'id_pelanggan' => $langganan->id_pelanggan,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $this->warn('No ID Pelanggan found for subscription: ' . $langganan->id);
            }
        }
        
        $this->info('Suspension process complete. Success: ' . $successCount . ' / ' . $pastDueSubscriptions->count());
        
        return 0;
    }
}