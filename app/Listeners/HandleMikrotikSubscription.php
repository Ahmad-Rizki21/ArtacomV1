<?php

namespace App\Listeners;

use App\Events\LanggananStatusChanged;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleMikrotikSubscription implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LanggananStatusChanged $event): void
    {
        $langganan = $event->langganan;
        $action = ($langganan->user_status === 'Aktif') ? 'activate' : 'suspend';

        try {
            $mikrotikManager = app(MikrotikSubscriptionManager::class);
            $mikrotikManager->handleSubscriptionStatus($langganan, $action);
            Log::info("Job Mikrotik berhasil dijalankan untuk pelanggan #{$langganan->pelanggan_id}", ['action' => $action]);
        } catch (\Exception $e) {
            Log::error("Job Mikrotik GAGAL untuk pelanggan #{$langganan->pelanggan_id}", ['error' => $e->getMessage()]);
            $this->release(30); // Coba lagi dalam 30 detik jika gagal
        }
    }
}