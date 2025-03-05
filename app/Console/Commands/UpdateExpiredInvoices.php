<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateExpiredInvoices extends Command
{
    /**
     * Nama dan deskripsi command
     *
     * @var string
     */
    protected $signature = 'invoices:update-expired';
    protected $description = 'Periksa dan perbarui status invoice yang telah jatuh tempo';

    /**
     * Jalankan command
     */
    public function handle()
    {
        Log::info("🔄 Menjalankan cron job untuk memperbarui invoice yang sudah jatuh tempo");

        // Ambil semua invoice yang sudah melewati tanggal jatuh tempo tetapi belum lunas
        $invoices = Invoice::where('tgl_jatuh_tempo', '<', Carbon::now())
            ->where('status_invoice', 'Menunggu Pembayaran')
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->update(['status_invoice' => 'Kadaluarsa']);

            Log::info("🔴 Invoice Kadaluarsa", [
                'invoice_number' => $invoice->invoice_number,
                'tgl_jatuh_tempo' => $invoice->tgl_jatuh_tempo,
                'updated_at' => Carbon::now()
            ]);
        }

        $this->info(count($invoices) . " invoice telah diperbarui menjadi Kadaluarsa.");
    }
}
