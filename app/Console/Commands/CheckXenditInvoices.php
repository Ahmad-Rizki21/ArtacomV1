<?php

// app/Console/Commands/CheckXenditInvoiceStatus.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class CheckXenditInvoices extends Command
{
    protected $signature = 'xendit:check-invoice-status';
    protected $description = 'Check and update status of pending Xendit invoices';

    public function handle()
    {
        // Cari invoice yang masih pending
        $pendingInvoices = Invoice::where('status_invoice', 'Menunggu Pembayaran')
            ->whereNotNull('xendit_id')
            ->get();

        foreach ($pendingInvoices as $invoice) {
            $invoice->updateStatusFromXendit();
        }

        $this->info('Invoice status check completed.');
    }
}