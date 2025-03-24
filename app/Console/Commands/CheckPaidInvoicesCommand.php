<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Langganan;
use App\Services\MikrotikConnectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckPaidInvoicesCommand extends Command
{
    protected $signature = 'invoice:check-paid-status';
    
    protected $description = 'Check status of invoices that have been paid via Xendit webhook';
    
    public function handle()
    {
        $this->info('Checking paid invoices status...');
        
        // Ambil invoice yang berstatus paid dalam 24 jam terakhir
        // yang mungkin belum diproses oleh webhook (sebagai fallback)
        $recentlyPaidInvoices = Invoice::where('status', 'paid')
            ->where('paid_at', '>=', Carbon::now()->subDay())
            ->whereNull('processed_at')
            ->get();
            
        $this->info('Found ' . $recentlyPaidInvoices->count() . ' recently paid invoices to process');
        
        $mikrotikService = new MikrotikConnectionService();
        $successCount = 0;
        
        foreach ($recentlyPaidInvoices as $invoice) {
            $this->info('Processing invoice ID: ' . $invoice->id);
            
            // Cari langganan terkait
            $langganan = Langganan::find($invoice->langganan_id);
            
            if (!$langganan) {
                $this->error('Langganan not found for invoice: ' . $invoice->id);
                continue;
            }
            
            // Perbarui tgl_invoice_terakhir jika belum
            if ($langganan->tgl_invoice_terakhir < $invoice->paid_at->format('Y-m-d')) {
                $langganan->tgl_invoice_terakhir = $invoice->paid_at->format('Y-m-d');
            }
            
            // Update last_processed_invoice
            $langganan->last_processed_invoice = $invoice->xendit_invoice_id;
            
            // Jika user_status sebelumnya 'Suspend', aktifkan kembali
            if ($langganan->user_status === 'Suspend') {
                $langganan->user_status = 'Aktif';
                
                // Aktifkan kembali di Mikrotik jika ID pelanggan tersedia
                if (!empty($langganan->id_pelanggan)) {
                    try {
                        $result = $mikrotikService->updatePppoeProfile(
                            $langganan->id_pelanggan, 
                            $langganan->profile_pppoe
                        );
                        
                        if ($result) {
                            $successCount++;
                            $this->info('Successfully reactivated in Mikrotik: ' . $langganan->id_pelanggan);
                        } else {
                            $this->error('Failed to reactivate in Mikrotik: ' . $langganan->id_pelanggan);
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
            
            // Simpan perubahan
            $langganan->save();
            
            // Tandai invoice sebagai telah diproses
            $invoice->processed_at = Carbon::now();
            $invoice->save();
        }
        
        $this->info('Paid invoices process complete. Success: ' . $successCount . ' / ' . $recentlyPaidInvoices->count());
        
        return 0;
    }
}