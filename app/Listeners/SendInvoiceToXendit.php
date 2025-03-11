<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Services\XenditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

class SendInvoiceToXendit
{
    protected $xenditService;

    /**
     * Create the event listener.
     */
    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event)
    {
        $invoice = $event->invoice;
        $traceId = uniqid('xendit_', true);

        Log::info('Processing invoice event', [
            'invoice_number' => $invoice->invoice_number,
            'trace_id' => $traceId
        ]);

        // Periksa apakah invoice sudah memiliki payment_link atau xendit_id
        if (!empty($invoice->payment_link) || !empty($invoice->xendit_id) || !empty($invoice->xendit_external_id)) {
            Log::info('Invoice sudah diproses oleh Xendit, melewati pembuatan', [
                'invoice_number' => $invoice->invoice_number,
                'payment_link' => $invoice->payment_link,
                'xendit_id' => $invoice->xendit_id,
                'trace_id' => $traceId
            ]);
            return;
        }

        // Cek apakah invoice sudah pernah diproses atau dikirim
        if (in_array($invoice->status_invoice, ['Selesai', 'Lunas', 'Dibayar'])) {
            Log::info('Invoice sudah selesai dibayar, melewati pengiriman ke Xendit.', [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status_invoice,
                'trace_id' => $traceId
            ]);
            return;
        }

        try {
            // Gunakan lock untuk mencegah race condition
            DB::transaction(function() use ($invoice, $traceId) {
                // Refresh invoice dari database dan lock
                $lockedInvoice = Invoice::where('id', $invoice->id)
                    ->lockForUpdate()
                    ->first();
                
                if (!$lockedInvoice) {
                    Log::error('Invoice tidak ditemukan setelah lock', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'trace_id' => $traceId
                    ]);
                    return;
                }
                
                // Cek lagi setelah lock untuk mencegah race condition
                if (!empty($lockedInvoice->payment_link) || !empty($lockedInvoice->xendit_id) || !empty($lockedInvoice->xendit_external_id)) {
                    Log::info('Invoice terkunci sudah diproses, melewati', [
                        'invoice_number' => $lockedInvoice->invoice_number,
                        'trace_id' => $traceId
                    ]);
                    return;
                }

                Log::info('Mencoba membuat invoice di Xendit', [
                    'invoice_number' => $lockedInvoice->invoice_number,
                    'brand' => $lockedInvoice->brand,
                    'trace_id' => $traceId
                ]);

                // Tandai invoice sedang diproses untuk mencegah duplikasi
                $lockedInvoice->is_processing = true;
                $lockedInvoice->save();

                // Gunakan layanan Xendit untuk mengirim invoice
                $result = $this->xenditService->createInvoice($lockedInvoice, $traceId);

                if ($result['status'] === 'success') {
                    // Update model invoice dengan data dari Xendit
                    $lockedInvoice->payment_link = $result['invoice_url'];
                    $lockedInvoice->xendit_id = $result['xendit_id'];
                    $lockedInvoice->xendit_external_id = $result['external_id'] ?? $lockedInvoice->invoice_number;
                    $lockedInvoice->status_invoice = 'Menunggu Pembayaran';
                    $lockedInvoice->is_processing = false;
                    $lockedInvoice->save();

                    Log::info('Berhasil membuat invoice di Xendit', [
                        'invoice_number' => $lockedInvoice->invoice_number,
                        'payment_link' => $lockedInvoice->payment_link,
                        'xendit_id' => $lockedInvoice->xendit_id,
                        'trace_id' => $traceId
                    ]);
                } else {
                    // Tandai invoice tidak lagi diproses
                    $lockedInvoice->is_processing = false;
                    $lockedInvoice->save();
                    
                    Log::error('Gagal mengirim invoice ke Xendit', [
                        'invoice_number' => $lockedInvoice->invoice_number, 
                        'error' => $result['error'] ?? 'Unknown error',
                        'trace_id' => $traceId
                    ]);
                    
                    throw new \Exception($result['error'] ?? 'Gagal membuat invoice di Xendit');
                }
            }, 3); // Retry 3 kali jika terjadi deadlock
            
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim invoice ke Xendit', [
                'invoice_number' => $invoice->invoice_number,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'trace_id' => $traceId
            ]);
        }
    }
}