<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Services\MikrotikSubscriptionManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckOverdueSubscriptions extends Command
{
    protected $signature = 'app:check-overdue-subscriptions';
    protected $description = 'Check for overdue subscriptions and suspend them in Mikrotik';
    protected $mikrotikManager;

    public function __construct(MikrotikSubscriptionManager $mikrotikManager)
    {
        parent::__construct();
        $this->mikrotikManager = $mikrotikManager;
    }

    public function handle()
    {
        $this->info('Starting overdue subscription check...');
        $now = Carbon::now();
        $this->info('Current date: ' . $now->format('Y-m-d'));

        // Hanya proses langganan yang lewat dari tanggal jatuh tempo lebih dari 1 hari
        $overdueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<', $now->subDay()->format('Y-m-d'))
            ->where('user_status', 'Aktif')
            ->get();

        $this->info('Found ' . $overdueSubscriptions->count() . ' overdue subscriptions');

        if ($overdueSubscriptions->isEmpty()) {
            $this->info('No overdue subscriptions to suspend. Exiting.');
            return 0;
        }

        $this->info('Processing ' . $overdueSubscriptions->count() . ' overdue subscriptions...');
        $successCount = 0;

        foreach ($overdueSubscriptions as $langganan) {
            $this->info('Processing subscription ID: ' . $langganan->id . ' for pelanggan ID: ' . $langganan->pelanggan_id);

            $latestInvoice = $langganan->invoices()
                ->where('tgl_jatuh_tempo', $langganan->tgl_jatuh_tempo)
                ->orderBy('tgl_invoice', 'desc')
                ->first();

            if ($latestInvoice && $latestInvoice->status_invoice === 'Lunas' && $latestInvoice->tgl_pembayaran) {
                $this->info('Paid invoice found for customer ID: ' . $langganan->pelanggan_id . '. Skipping suspension.');
                continue;
            }

            $oldStatus = $langganan->user_status;
            $langganan->user_status = 'Suspend';
            $langganan->save();

            Log::info('Suspending overdue subscription', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'previous_status' => $oldStatus,
                'new_status' => 'Suspend',
                'due_date' => $langganan->tgl_jatuh_tempo,
                'suspension_date' => $now->format('Y-m-d')
            ]);

            $result = $this->mikrotikManager->handleSubscriptionStatus($langganan, 'suspend');
            if ($result) {
                $successCount++;
                $this->info('Successfully suspended: ' . $langganan->pelanggan_id);
            } else {
                $this->error('Failed to suspend: ' . $langganan->pelanggan_id);
                Log::error('Failed to suspend subscription in Mikrotik', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'dataTeknis' => optional($langganan->pelanggan)->dataTeknis ? 'Exists' : 'Missing'
                ]);
            }
        }

        $this->info('Suspension process complete. Success: ' . $successCount . ' / ' . $overdueSubscriptions->count());
        return 0;
    }
}