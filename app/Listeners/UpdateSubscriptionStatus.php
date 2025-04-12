<?php

namespace App\Listeners;

use App\Models\Langganan;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateSubscriptionStatus
{
    protected $mikrotikManager;

    public function __construct(MikrotikSubscriptionManager $mikrotikManager)
    {
        $this->mikrotikManager = $mikrotikManager;
    }

    public function handle($event)
    {
        $invoice = $event->invoice;
        $langganan = Langganan::where('pelanggan_id', $invoice->pelanggan_id)->first();

        if ($langganan && $invoice->status_invoice === 'Lunas') {
            $currentDueDate = Carbon::parse($langganan->tgl_jatuh_tempo);
            if ($currentDueDate->lt(Carbon::now())) {
                $currentDueDate = Carbon::now()->endOfMonth()->day(1)->addMonth(); // Set ke 1 bulan berikutnya
            }
            $langganan->user_status = 'Aktif';
            $langganan->tgl_jatuh_tempo = $currentDueDate->addMonth()->day(1)->format('Y-m-d'); // Misal 1 Juni 2025
            $langganan->save();

            Log::info('Subscription reactivated due to payment', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'new_due_date' => $langganan->tgl_jatuh_tempo,
                'status' => 'Aktif'
            ]);

            $this->mikrotikManager->handleSubscriptionStatus($langganan, 'activate');
        }
    }
}