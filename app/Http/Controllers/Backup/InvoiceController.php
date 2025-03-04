<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Xendit\Xendit;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Buat invoice baru
     */
    public function createInvoice(Request $request)
    {
        try {
            // Validasi request
            $validated = $request->validate([
                'pelanggan_id' => 'required|exists:pelanggans,id',
                'tgl_invoice' => 'required|date',
                'tgl_jatuh_tempo' => 'required|date|after:tgl_invoice'
            ]);

            // Buat invoice
            $invoice = Invoice::create($validated);

            // Buat invoice di Xendit
            $xenditResult = $this->xenditService->createInvoice($invoice);

            if ($xenditResult['status'] === 'success') {
                // Update invoice dengan informasi Xendit
                $invoice->update([
                    'payment_link' => $xenditResult['invoice_url'],
                    'xendit_id' => $xenditResult['xendit_id'],
                    'xendit_external_id' => $xenditResult['external_id']
                ]);

                return response()->json([
                    'message' => 'Invoice berhasil dibuat',
                    'invoice' => $invoice
                ]);
            }

            return response()->json([
                'message' => 'Gagal membuat invoice di Xendit'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Kesalahan membuat invoice', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Periksa status invoice
     */
    public function checkStatus($invoiceNumber)
    {
        try {
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->firstOrFail();

            return response()->json([
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status_invoice,
                'paid_amount' => $invoice->paid_amount,
                'paid_at' => $invoice->paid_at
            ]);

        } catch (\Exception $e) {
            Log::error('Kesalahan memeriksa status invoice', [
                'invoice_number' => $invoiceNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Invoice tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update status invoice manual (untuk debugging)
     */
    public function updateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice_number' => 'required|exists:invoices,invoice_number',
                'status' => 'required|in:Menunggu Pembayaran,Lunas,Kadaluarsa,Selesai'
            ]);

            $invoice = Invoice::where('invoice_number', $validated['invoice_number'])->first();
            $invoice->update(['status_invoice' => $validated['status']]);

            return response()->json([
                'message' => 'Status invoice berhasil diupdate',
                'invoice' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('Kesalahan update status invoice', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Gagal update status invoice'
            ], 500);
        }
    }

  

}