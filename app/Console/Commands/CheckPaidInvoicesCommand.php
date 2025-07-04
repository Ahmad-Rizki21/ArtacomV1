<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Langganan; // <-- TAMBAHKAN INI
use App\Services\XenditService;
use App\Services\MikrotikSubscriptionManager; // <-- TAMBAHKAN INI
use Illuminate\Support\Facades\Log;

class CheckPaidInvoicesCommand extends Command
{
    protected $signature = 'invoice:check-paid-status';
    protected $description = 'Check paid invoices and activate subscriptions on Mikrotik.';
    
    protected $xenditService;
    protected $mikrotikManager; // <-- TAMBAHKAN INI

    public function __construct(XenditService $xenditService, MikrotikSubscriptionManager $mikrotikManager) // <-- MODIFIKASI INI
    {
        parent::__construct();
        $this->xenditService = $xenditService;
        $this->mikrotikManager = $mikrotikManager; // <-- TAMBAHKAN INI
    }
    
    public function handle()
    {
        $this->info('Starting to check status of unpaid invoices...');
        
        $unpaidInvoices = Invoice::where('status_invoice', 'Menunggu Pembayaran')->get();

        if ($unpaidInvoices->isEmpty()) {
            $this->info('No unpaid invoices to check.');
            return self::SUCCESS;
        }

        $this->info("Found {$unpaidInvoices->count()} unpaid invoice(s) to check.");

        foreach ($unpaidInvoices as $invoice) {
            if (empty($invoice->xendit_id) || empty($invoice->brand)) {
                $this->warn("Skipping Invoice #{$invoice->invoice_number}: missing xendit_id or brand.");
                continue;
            }

            $this->line("-> Checking Invoice #{$invoice->invoice_number}...");
            $result = $this->xenditService->checkInvoiceStatus($invoice->xendit_id, $invoice->brand);

            if ($result && isset($result['status']) && in_array($result['status'], ['PAID', 'SETTLED'])) {
                // Status sudah diperbarui di dalam service, kita refresh untuk dapat data baru
                $invoice->refresh(); 
                $this->info("SUCCESS: Status for Invoice #{$invoice->invoice_number} is now {$invoice->status_invoice}.");
                Log::info("[Scheduler] Invoice #{$invoice->invoice_number} updated to {$invoice->status_invoice}.");

                // === LOGIKA BARU UNTUK AKTIVASI MIKROTIK ===
                $this->activateSubscription($invoice);
                // ===========================================

            } else {
                $status = $result['status'] ?? 'UNKNOWN';
                $this->line("   Status is still {$status}. No update needed.");
            }
        }

        $this->info('Checking unpaid invoices status completed.');
        return self::SUCCESS;
    }

    /**
     * Mengaktifkan langganan setelah invoice dibayar.
     *
     * @param Invoice $invoice
     */
    protected function activateSubscription(Invoice $invoice)
    {
        $this->info("   Attempting to activate subscription for Pelanggan ID: {$invoice->pelanggan_id}");

        // 1. Cari langganan yang relevan
        $langganan = Langganan::where('pelanggan_id', $invoice->pelanggan_id)->first();

        if (!$langganan) {
            $this->error("   ERROR: Langganan not found for Pelanggan ID: {$invoice->pelanggan_id}.");
            Log::error("[Scheduler] Activation failed: Langganan not found for Pelanggan ID {$invoice->pelanggan_id}.");
            return;
        }

        // 2. Cek apakah statusnya memang perlu diaktifkan (misalnya dari 'Suspend')
        if ($langganan->user_status === 'Aktif') {
            $this->info("   INFO: Subscription is already active. No action needed.");
            return;
        }

        // 3. Update status langganan di database
        $langganan->user_status = 'Aktif';
        $langganan->save();
        $this->info("   DB Updated: Subscription status changed to 'Aktif'.");

        // 4. Panggil MikrotikManager untuk mengaktifkan di router
        $result = $this->mikrotikManager->handleSubscriptionStatus($langganan, 'activate');

        if ($result) {
            $this->info("   MIKROTIK SUCCESS: Subscription for Pelanggan ID {$langganan->pelanggan_id} has been activated on the router.");
            Log::info("[Scheduler] Mikrotik activation successful for Pelanggan ID {$langganan->pelanggan_id}.");
        } else {
            $this->error("   MIKROTIK FAILED: Failed to activate subscription on the router for Pelanggan ID {$langganan->pelanggan_id}.");
            Log::error("[Scheduler] Mikrotik activation failed for Pelanggan ID {$langganan->pelanggan_id}.");
        }
    }
}