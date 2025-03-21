<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;
use Carbon\Carbon;

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
            // Ambil token dari header webhook Xendit
            $webhookToken = $request->header('X-Callback-Token');

            // Log semua data yang diterima untuk keperluan debugging
            Log::info('Xendit Webhook Received', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'token_received' => $webhookToken ? substr($webhookToken, 0, 5) . '***' : 'Missing'
            ]);

            // Proses webhook dengan menyertakan token untuk verifikasi
            $data = $this->xenditService->processWebhook($request->all(), $webhookToken);
            
            // Jika status error, kembalikan respons sesuai dengan kode 400
            if (isset($data['status']) && $data['status'] === 'error') {
                Log::warning('Webhook processing returned error', [
                    'error' => $data['message'] ?? 'Unknown error'
                ]);
                return response()->json($data, 400);
            }
            
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
        try {
            // Ambil token dari header webhook Xendit
            $webhookToken = $request->header('X-Callback-Token');
            
            // Validasi token webhook
            if (!$this->xenditService->validateWebhookToken($webhookToken)) {
                Log::warning('Invalid webhook token received', [
                    'token_received' => $webhookToken ? substr($webhookToken, 0, 5) . '***' : 'Missing'
                ]);
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Invalid webhook token'
                ], 401);
            }
            
            // Log data webhook
            Log::info('Xendit Webhook Data', $request->all());

            // Ambil data dari webhook
            $externalId = $request->input('external_id');
            $status = $request->input('status');
            $paidAmount = $request->input('paid_amount');
            $paidAt = $request->input('paid_at');

            // Cari invoice berdasarkan external_id
            $invoice = Invoice::where('invoice_number', $externalId)
                            ->orWhere('xendit_external_id', $externalId)
                            ->first();

            if (!$invoice) {
                Log::error('Invoice tidak ditemukan: ' . $externalId);
                return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
            }

            // Pastikan tanggal invoice tersedia
            if (empty($invoice->tgl_invoice)) {
                Log::warning('tgl_invoice kosong pada invoice ' . $externalId . ', menggunakan tanggal saat ini');
                $invoice->tgl_invoice = now()->format('Y-m-d');
                $invoice->save();
            }

            // Tampilkan informasi tentang invoice yang akan diupdate
            Log::info('Update invoice dari webhook Xendit', [
                'invoice_number' => $externalId,
                'status' => $status, 
                'tgl_invoice' => $invoice->tgl_invoice,
                'paid_at' => $paidAt
            ]);

            // Update status invoice dari webhook
            $invoice->updateStatusFromWebhook($status, $paidAmount, $paidAt);

            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('Kesalahan pada callback Xendit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => 'Terjadi kesalahan saat memproses callback'
            ], 500);
        }
    }
}