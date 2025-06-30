<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;          // Pastikan path model Invoice Anda benar
use App\Services\XenditService;  // Import service yang sudah Anda buat
use Illuminate\Support\Facades\Log;

class CheckPaidInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:check-paid-status';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of unpaid invoices from Xendit and update them if necessary (fallback for webhooks).';
    
    /**
     * Instance dari Xendit Service.
     *
     * @var \App\Services\XenditService
     */
    protected $xenditService;

    /**
     * Create a new command instance.
     * Menggunakan Dependency Injection untuk mendapatkan XenditService.
     *
     * @param \App\Services\XenditService $xenditService
     * @return void
     */
    public function __construct(XenditService $xenditService)
    {
        parent::__construct();
        $this->xenditService = $xenditService;
    }
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to check status of unpaid invoices...');
        
        // 1. Ambil semua invoice yang statusnya 'Menunggu Pembayaran'.
        // Saya menggunakan status ini berdasarkan konstanta STATUS_MAP di service Anda.
        $unpaidInvoices = Invoice::where('status_invoice', 'Menunggu Pembayaran')->get();

        if ($unpaidInvoices->isEmpty()) {
            $this->info('No invoices with status "Menunggu Pembayaran" found to check.');
            // Menulis ke log utama juga untuk jejak
            Log::info('[Scheduler] No unpaid invoices to check.');
            return self::SUCCESS; // Command selesai dengan sukses
        }

        $this->info("Found {$unpaidInvoices->count()} unpaid invoice(s) to check.");
        Log::info("[Scheduler] Found {$unpaidInvoices->count()} unpaid invoice(s) to check.");

        foreach ($unpaidInvoices as $invoice) {
            // 2. Pastikan invoice memiliki 'xendit_id' dan 'brand' untuk bisa dicek
            if (empty($invoice->xendit_id) || empty($invoice->brand)) {
                $logMessage = "Skipping Invoice #{$invoice->invoice_number}: missing xendit_id or brand.";
                $this->warn($logMessage);
                Log::warning("[Scheduler] " . $logMessage);
                continue;
            }

            $this->line("-> Checking Invoice #{$invoice->invoice_number} (Xendit ID: {$invoice->xendit_id})");

            // 3. Panggil metode checkInvoiceStatus dari service Anda.
            // Metode ini sudah berisi logika untuk update database jika lunas.
            $result = $this->xenditService->checkInvoiceStatus($invoice->xendit_id, $invoice->brand);

            if ($result && isset($result['status'])) {
                // Logika pembaruan sudah ada di dalam checkInvoiceStatus,
                // jadi di sini kita hanya perlu memberikan feedback di console/log.
                if (in_array($result['status'], ['PAID', 'SETTLED'])) {
                    // Refresh model untuk mendapatkan status terbaru
                    $invoice->refresh(); 
                    $successMessage = "SUCCESS: Status for Invoice #{$invoice->invoice_number} was updated to {$invoice->status_invoice} via scheduler.";
                    $this->info($successMessage);
                    Log::info("[Scheduler] " . $successMessage);
                } else {
                    $this->line("   Status is still {$result['status']}. No update needed.");
                }
            } else {
                $errorMessage = "FAILED: Could not retrieve status for Invoice #{$invoice->invoice_number}.";
                $this->error($errorMessage);
                Log::error("[Scheduler] " . $errorMessage);
            }
        }

        $this->info('Checking unpaid invoices status completed.');
        Log::info('[Scheduler] Checking unpaid invoices status completed.');
        return self::SUCCESS;
    }
}
