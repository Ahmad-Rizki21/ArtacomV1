<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Langganan;
use App\Services\XenditService;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Support\Facades\Log;

class CheckPaidInvoicesCommand extends Command
{
    protected $signature = 'invoice:check-paid-status';
    protected $description = 'Check paid invoices and activate subscriptions on Mikrotik.';
    
    protected $xenditService;
    protected $mikrotikManager;

    public function __construct(XenditService $xenditService, MikrotikSubscriptionManager $mikrotikManager)
    {
        parent::__construct();
        $this->xenditService = $xenditService;
        $this->mikrotikManager = $mikrotikManager;
    }
    
    public function handle()
    {
        $this->info('Starting to check status of unpaid invoices...');
        
        // REVISI 1: Gunakan Eager Loading 'langganan'.
        // Ini akan mengambil semua data langganan yang terkait dalam satu query tambahan,
        // menghilangkan N+1 query problem di dalam loop.
        $unpaidInvoices = Invoice::with('langganan')
            ->where('status_invoice', 'Menunggu Pembayaran')
            ->get();

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
                $invoice->refresh(); 
                $this->info("SUCCESS: Status for Invoice #{$invoice->invoice_number} is now {$invoice->status_invoice}.");
                Log::info("[Scheduler] Invoice #{$invoice->invoice_number} updated to {$invoice->status_invoice}.");

                $this->activateSubscription($invoice);

            } else {
                $status = $result['status'] ?? 'UNKNOWN';
                $this->line("   Status is still {$status}. No update needed.");
            }
        }

        $this->info('Checking unpaid invoices status completed.');
        return self::SUCCESS;
    }

    protected function activateSubscription(Invoice $invoice)
    {
        $this->info("   Attempting to activate subscription for Pelanggan ID: {$invoice->pelanggan_id}");

        // REVISI 2: Tidak perlu query lagi, langsung akses dari relasi yang sudah di-load.
        $langganan = $invoice->langganan;

        if (!$langganan) {
            $this->error("   ERROR: Langganan not found for Pelanggan ID: {$invoice->pelanggan_id}.");
            Log::error("[Scheduler] Activation failed: Langganan not found for Pelanggan ID {$invoice->pelanggan_id}.");
            return;
        }

        if ($langganan->user_status === 'Aktif') {
            $this->info("   INFO: Subscription is already active. No action needed.");
            return;
        }

        $langganan->user_status = 'Aktif';
        $langganan->save();
        $this->info("   DB Updated: Subscription status changed to 'Aktif'.");

        // Panggil MikrotikManager. Pastikan logic di dalamnya juga efisien.
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
