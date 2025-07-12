<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
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
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $this->info("Memulai proses suspend pelanggan dengan tanggal jatuh tempo: {$yesterday} atau sebelumnya");
        
        $query = Langganan::query()->where('user_status', 'Aktif');
            
        if (!$this->option('force')) {
            $query->where('tgl_jatuh_tempo', '<=', $yesterday);
        }
        
        $overdueSubscriptions = $query->with(['invoices', 'pelanggan.dataTeknis'])->get();
        
        $this->info("Ditemukan {$overdueSubscriptions->count()} langganan aktif yang sudah melewati tanggal jatuh tempo");
        
        if ($overdueSubscriptions->isEmpty()) {
            $this->info("Tidak ada langganan yang perlu disuspend.");
            return self::SUCCESS;
        }
        
        $successCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        
        foreach ($overdueSubscriptions as $langganan) {
            $this->info("Memeriksa langganan ID: {$langganan->id} untuk pelanggan ID: {$langganan->pelanggan_id}");
            
            $hasUnpaidInvoice = $langganan->invoices
                ->where('status_invoice', 'Menunggu Pembayaran')
                ->isNotEmpty();
                
            if (!$hasUnpaidInvoice) {
                $this->info("Pelanggan ID: {$langganan->pelanggan_id} tidak memiliki invoice yang belum dibayar. Dilewati.");
                $skippedCount++;
                continue;
            }
            
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            Log::info("Mengubah status pelanggan menjadi Suspend karena sudah melewati jatuh tempo", [
                'pelanggan_id' => $langganan->pelanggan_id,
                'status_baru' => 'Suspend',
            ]);
            
            $dataTeknis = $langganan->pelanggan->dataTeknis ?? null;

            if (!$dataTeknis || !$dataTeknis->id_pelanggan) {
                $this->warn("Pelanggan ID: {$langganan->pelanggan_id} tidak memiliki data teknis atau ID pelanggan. Gagal suspend di Mikrotik.");
                $failedCount++;
                continue;
            }
            
            try {
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
                Log::error('Error suspend pelanggan di Mikrotik', ['error' => $e->getMessage()]);
            }
        }
        
        $this->info("Proses suspend selesai: Berhasil: {$successCount}, Dilewati: {$skippedCount}, Gagal: {$failedCount}");
        
        return self::SUCCESS;
    }
}
