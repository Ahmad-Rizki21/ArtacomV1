<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceService;

class CreateMonthlyInvoices extends Command
{
    protected $signature = 'invoice:create-monthly';
    protected $description = 'Buat invoice bulanan untuk pelanggan yang akan jatuh tempo';

    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        parent::__construct();
        $this->invoiceService = $invoiceService;
    }

    public function handle()
    {
        $this->info('Memulai proses pembuatan invoice bulanan...');
        
        try {
            $this->invoiceService->buatInvoiceSebelumJatuhTempo();
            $this->info('Proses pembuatan invoice berhasil diselesaikan.');
        } catch (\Exception $e) {
            $this->error('Gagal membuat invoice: ' . $e->getMessage());
        }

        return 0;
    }
}