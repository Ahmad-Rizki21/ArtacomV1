<?php

namespace App\Observers;

use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    public function updated(Invoice $invoice)
    {
        // Cek apakah status_invoice berubah
        if ($invoice->isDirty('status_invoice')) {
            Log::info('Status invoice berubah', [
                'invoice_number' => $invoice->invoice_number,
                'old_status' => $invoice->getOriginal('status_invoice'),
                'new_status' => $invoice->status_invoice,
            ]);

            // Simpan notifikasi sementara di cache
            Cache::put('invoice_notification_' . $invoice->id, [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status_invoice,
                'pelanggan' => $invoice->pelanggan->nama,
                'total_harga' => number_format($invoice->total_harga, 0, ',', '.'),
                'timestamp' => now()->toDateTimeString(),
            ], now()->addMinutes(5)); // Simpan selama 5 menit

            // Tambahkan notifikasi Filament
            Notification::make()
                ->title('Status Invoice Berubah')
                ->body("Invoice #{$invoice->invoice_number} dari {$invoice->pelanggan->nama} kini {$invoice->status_invoice}.")
                ->success()
                ->send();
        }
    }
}