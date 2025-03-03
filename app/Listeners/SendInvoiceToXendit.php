<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Services\XenditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendInvoiceToXendit
{
    public function handle(InvoiceCreated $event)
    {
        $invoice = $event->invoice;
        
        // Periksa apakah invoice sudah memiliki payment_link
        if (!empty($invoice->payment_link)) {
            Log::info('Invoice sudah memiliki payment link, melewati pembuatan di Xendit', [
                'invoice_number' => $invoice->invoice_number,
                'payment_link' => $invoice->payment_link
            ]);
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $apiKey = $invoice->brand === 'jakinet' ? env('XENDIT_API_KEY_JAKINET') : env('XENDIT_API_KEY_JELANTIK');
            
            // Ganti dengan cara yang tepat untuk mengakses brand name berdasarkan id_brand
            $brandName = \App\Models\HargaLayanan::where('id_brand', $invoice->brand)->value('brand') ?? $invoice->brand;
            
            Log::info('Mencoba membuat invoice di Xendit', [
                'invoice_number' => $invoice->invoice_number,
                'brand' => $brandName
            ]);
            
            // Gunakan layanan Xendit untuk mengirim invoice
            $xenditService = new XenditService();
            $result = $xenditService->createInvoice($invoice);

            if ($result['status'] === 'success') {
                $invoice->payment_link = $result['invoice_url'];
                $invoice->status_invoice = 'Menunggu Pembayaran';
                $invoice->save();
                
                Log::info('Berhasil membuat invoice di Xendit', [
                    'invoice_number' => $invoice->invoice_number,
                    'payment_link' => $invoice->payment_link
                ]);
                
                DB::commit();
            } else {
                // Log error jika pengiriman invoice gagal
                Log::error('Gagal mengirim invoice ke Xendit', [
                    'invoice_number' => $invoice->invoice_number, 
                    'error' => $result['error']
                ]);
                
                DB::rollBack();
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim invoice ke Xendit', [
                'invoice_number' => $invoice->invoice_number,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            DB::rollBack();
        }
    }
}