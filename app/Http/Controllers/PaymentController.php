<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;

class PaymentController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    // Buat invoice baru
    public function createInvoice(Request $request)
    {
        try {
            $validated = $request->validate([
                'pelanggan_id' => 'required|exists:pelanggans,id',
                'tgl_invoice' => 'required|date',
                'tgl_jatuh_tempo' => 'required|date|after:tgl_invoice'
            ]);

            $invoice = Invoice::create($validated);
            $result = $this->xenditService->createInvoice($invoice);

            if ($result['status'] === 'success') {
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
            return response()->json(['message' => 'Terjadi kesalahan saat membuat invoice'], 500);
        }
    }

    // Periksa status invoice
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
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }
    }

    // Handle Xendit Webhook
    public function handleWebhook(Request $request)
    {
        try {
            Log::info('Xendit Webhook Received', ['payload' => $request->all()]);
            $data = $this->xenditService->processWebhook($request->all());
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Kesalahan memperbarui invoice dari webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Terjadi kesalahan memperbarui invoice'], 500);
        }
    }

    public function handleXenditCallback(Request $request)
    {
        // Log data webhook
        Log::info('Xendit Webhook Data', $request->all());

        // Ambil data dari webhook
        $externalId = $request->input('external_id');
        $status = $request->input('status');
        $paidAmount = $request->input('paid_amount');
        $paidAt = $request->input('paid_at');

        // Cari invoice berdasarkan external_id
        $invoice = Invoice::where('invoice_number', $externalId)->first();

        if (!$invoice) {
            Log::error('Invoice tidak ditemukan: ' . $externalId);
            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        // Update status invoice dari webhook
        $invoice->updateStatusFromWebhook($status, $paidAmount, $paidAt);

        return response()->json(['status' => 'success']);
    }



}
