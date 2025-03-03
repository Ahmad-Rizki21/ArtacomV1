<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;  // Jika job harus masuk ke dalam queue

class CheckInvoiceStatus implements ShouldQueue  // Implementasikan ShouldQueue jika job masuk ke dalam queue
{
    use Dispatchable;  // Menambahkan trait Dispatchable

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $invoices = Invoice::where('status_invoice', 'Menunggu Pembayaran')->get();

        foreach ($invoices as $invoice) {
            $xenditId = $invoice->xendit_id;

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode(env('XENDIT_API_KEY_JAKINET') . ':'),
            ])->get('https://api.xendit.co/v2/invoices/' . $xenditId);

            $data = $response->json();

            if ($response->successful() && isset($data['status'])) {
                $status = $data['status'];

                // Perbarui status berdasarkan status dari Xendit
                if ($status == 'SETTLED') {
                    $invoice->status_invoice = 'Lunas';
                    $invoice->save();
                    Log::info('Invoice status updated', ['invoice_number' => $invoice->invoice_number, 'status' => 'Lunas']);
                } elseif ($status == 'EXPIRED') {
                    $invoice->status_invoice = 'Kadaluarsa';
                    $invoice->save();
                    Log::info('Invoice status updated', ['invoice_number' => $invoice->invoice_number, 'status' => 'Kadaluarsa']);
                }
            }
        }
    }
}
