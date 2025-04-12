<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Models\Invoice;
use App\Services\MikrotikSubscriptionManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SuspendDueSubscriptions extends Command
{
    protected $signature = 'invoice:suspend-due {--force : Force check all active subscriptions}';
    protected $description = 'Suspend pelanggan yang sudah jatuh tempo dengan invoice belum dibayar';

    protected $mikrotikManager;

    public function __construct(MikrotikSubscriptionManager $mikrotikManager)
    {
        parent::__construct();
        $this->mikrotikManager = $mikrotikManager;
    }

    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');
        
        $this->info("Memulai proses suspend pelanggan dengan tanggal jatuh tempo: {$yesterday} atau sebelumnya");
        
        // Filter langganan yang jatuh tempo kemarin atau sebelumnya dan masih aktif
        $query = Langganan::query()
            ->where('user_status', 'Aktif');
            
        if (!$this->option('force')) {
            // Cari langganan yang jatuh tempo kemarin atau sebelumnya (bukan hari ini)
            $query->where('tgl_jatuh_tempo', '<=', $yesterday);
        }
        
        $overdueSubscriptions = $query->get();
        
        $this->info("Ditemukan {$overdueSubscriptions->count()} langganan aktif yang sudah melewati tanggal jatuh tempo");
        
        if ($overdueSubscriptions->isEmpty()) {
            $this->info("Tidak ada langganan yang perlu disuspend.");
            return 0;
        }
        
        $successCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        
        foreach ($overdueSubscriptions as $langganan) {
            $this->info("Memeriksa langganan ID: {$langganan->id} untuk pelanggan ID: {$langganan->pelanggan_id}");
            
            // Periksa apakah sudah benar-benar melewati tanggal jatuh tempo
            if ($langganan->tgl_jatuh_tempo > $yesterday && !$this->option('force')) {
                $this->info("Pelanggan ID: {$langganan->pelanggan_id} belum melewati masa tenggang. Dilewati.");
                $skippedCount++;
                continue;
            }
            
            // Cek jika ada invoice pada bulan ini yang belum dibayar
            $hasUnpaidInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                ->where('status_invoice', 'Menunggu Pembayaran')
                ->exists();
                
            if (!$hasUnpaidInvoice) {
                $this->info("Pelanggan ID: {$langganan->pelanggan_id} tidak memiliki invoice yang belum dibayar. Dilewati.");
                $skippedCount++;
                continue;
            }
            
            // Suspend pelanggan
            $oldStatus = $langganan->user_status;
            
            // Update status di database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            Log::info("Mengubah status pelanggan menjadi Suspend karena sudah melewati jatuh tempo", [
                'pelanggan_id' => $langganan->pelanggan_id,
                'status_lama' => $oldStatus,
                'status_baru' => 'Suspend',
                'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo
            ]);
            
            // Update di Mikrotik
            $dataTeknis = optional($langganan->pelanggan)->dataTeknis;
            if (!$dataTeknis || !$dataTeknis->id_pelanggan) {
                $this->warn("Pelanggan ID: {$langganan->pelanggan_id} tidak memiliki data teknis atau ID pelanggan. Gagal suspend di Mikrotik.");
                $failedCount++;
                continue;
            }
            
            try {
                // Suspend di Mikrotik menggunakan MikrotikSubscriptionManager
                $result = $this->mikrotikManager->handleSubscriptionStatus($langganan, 'suspend');
                
                if ($result) {
                    $successCount++;
                    $this->info("✓ Berhasil suspend pelanggan ID: {$langganan->pelanggan_id} (ID Pelanggan: {$dataTeknis->id_pelanggan})");
                } else {
                    $failedCount++;
                    $this->error("✗ Gagal suspend pelanggan ID: {$langganan->pelanggan_id} di Mikrotik");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("✗ Error suspend pelanggan ID: {$langganan->pelanggan_id} - {$e->getMessage()}");
                Log::error('Error suspend pelanggan di Mikrotik', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->info("Proses suspend selesai:");
        $this->info("- Berhasil: {$successCount}");
        $this->info("- Dilewati: {$skippedCount}");
        $this->info("- Gagal: {$failedCount}");
        
        return 0;
    }
}