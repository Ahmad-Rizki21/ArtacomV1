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
        
        // 1. Langganan yang sudah melewati tanggal jatuh tempo
        $pastDueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<', $now->format('Y-m-d'))
            ->where('user_status', 'Aktif')
            ->get();
        
        // 2. Langganan yang akan jatuh tempo dalam 5 hari dan memiliki invoice yang belum dibayar
        $upcomingDueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<=', $now->copy()->addDays(5)->format('Y-m-d'))
            ->where('tgl_jatuh_tempo', '>', $now->format('Y-m-d'))
            ->where('user_status', 'Aktif')
            ->whereHas('invoices', function($query) {
                $query->where('status_invoice', 'Menunggu Pembayaran');
            })
            ->get();
        
        $this->info('Found ' . $pastDueSubscriptions->count() . ' past due subscriptions');
        $this->info('Found ' . $upcomingDueSubscriptions->count() . ' upcoming due subscriptions with unpaid invoices');
        
        // Gabungkan kedua koleksi
        $allSubscriptionsToSuspend = $pastDueSubscriptions->merge($upcomingDueSubscriptions);
        
        // Periksa total langganan yang perlu di-suspend
        if ($allSubscriptionsToSuspend->isEmpty()) {
            $this->info('No subscriptions to suspend found. Exiting.');
            return 0;
        }
        
        $this->info('Total ' . $allSubscriptionsToSuspend->count() . ' subscriptions will be suspended...');
        
        // Lanjutkan dengan proses suspend...
        $mikrotikService = new MikrotikConnectionService();
        $successCount = 0;
        
        foreach ($allSubscriptionsToSuspend as $langganan) {
            $this->info('Processing subscription ID: ' . $langganan->id . ' for pelanggan ID: ' . $langganan->pelanggan_id);
            
            // Simpan status lama untuk log
            $oldStatus = $langganan->user_status;
            
            // Update status di database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            // Update di Mikrotik jika ID Pelanggan tersedia
            if (!empty($langganan->id_pelanggan)) {
                try {
                    // PERHATIKAN: Pastikan parameter yang dikirim ke Mikrotik benar
                    // "suspended" (lowercase) atau "SUSPENDED" (uppercase) sesuai dengan konfigurasi Mikrotik Anda
                    $result = $mikrotikService->updatePppoeProfile($langganan->id_pelanggan, 'SUSPENDED');
                    
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
        
        $this->info('Suspension process complete. Success: ' . $successCount . ' / ' . $allSubscriptionsToSuspend->count());
        
        return 0;
    }
}