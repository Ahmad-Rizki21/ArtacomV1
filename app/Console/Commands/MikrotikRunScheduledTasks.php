<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MikrotikSubscriptionManager;
use App\Services\InvoiceService; // Anda mungkin perlu service ini
use Illuminate\Support\Facades\Log;

class MikrotikRunScheduledTasks extends Command
{
    protected $signature = 'mikrotik:run-all-tasks';
    protected $description = 'Runs essential MikroTik tasks like invoice checks and suspensions.';

    public function handle(MikrotikSubscriptionManager $subscriptionManager) {
        $this->info('--- ['.now().'] Starting MikroTik Task Runner ---');

        // TUGAS 1: Cek Invoice yang sudah dibayar dan aktifkan langganan
        $this->info('Running: Checking paid invoices and activating subscribers...');
        try {
            // Panggil command lain secara terprogram. Ini lebih baik daripada memanggil service
            // karena outputnya akan tetap di console.
            $this->call('invoice:check-paid-status');
        } catch (\Exception $e) {
            $this->error('An error occurred during invoice:check-paid-status.');
            Log::error($e->getMessage());
        }
        $this->info('Finished checking paid invoices.');
        
        // TUGAS 2: Cek pelanggan yang jatuh tempo untuk di-suspend
        $this->info('Running: Checking overdue subscriptions for suspension...');
        try {
            $subscriptionManager->processDueDateSubscriptions();
        } catch (\Exception $e) {
            $this->error('An error occurred during subscription suspension process.');
            Log::error($e->getMessage());
        }
        $this->info('Finished checking for suspensions.');

        $this->info('--- MikroTik Task Runner Finished ---');
        return self::SUCCESS;
    }
}