<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPaidInvoicesCommand extends Command
{
    protected $signature = 'invoice:check-paid-status';
    
    protected $description = 'Check status of invoices that have been paid via Xendit webhook';
    
    public function handle()
    {
        // Logic untuk memeriksa status invoice yang telah dibayar
        // Anda dapat menambahkan logika untuk memproses data dari webhook atau database
        
        $this->info('Checking paid invoices status completed');
    }
}