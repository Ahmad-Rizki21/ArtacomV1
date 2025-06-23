<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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


    public function checkInvoiceNotifications()
    {
        // Ambil semua notifikasi dari cache
        $notifications = Cache::getKeys()
            ->filter(fn($key) => str_starts_with($key, 'invoice_notification_'))
            ->map(fn($key) => Cache::get($key))
            ->values()
            ->toArray();

        // Kosongkan cache setelah diambil untuk mencegah duplikasi
        foreach (Cache::getKeys() as $key) {
            if (str_starts_with($key, 'invoice_notification_')) {
                Cache::forget($key);
            }
        }

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
        ]);
    }

    // Handle Xendit Webhook
    // PaymentController.php
public function handleWebhook(Request $request)
{
    try {
        // Log semua data yang diterima
        Log::info('Webhook data received', [
            'data' => $request->all()
        ]);

        // Validasi dasar - pastikan ada data yang diperlukan
        $data = $request->all();
        if (empty($data['external_id']) || empty($data['status'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing required data'
            ], 400);
        }

        // Cari invoice berdasarkan external_id
        $invoice = Invoice::where('xendit_external_id', $data['external_id'])
                        ->orWhere('invoice_number', $data['external_id'])
                        ->first();

        if (!$invoice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invoice tidak ditemukan'
            ], 404);
        }

        // Update status invoice
        $newStatus = [
            'PENDING' => 'Menunggu Pembayaran',
            'PAID' => 'Lunas',
            'SETTLED' => 'Selesai',
            'EXPIRED' => 'Kadaluarsa'
        ][$data['status']] ?? 'Tidak Diketahui';

        // Update invoice data
        $invoice->status_invoice = $newStatus;
        $invoice->xendit_id = $data['id'] ?? null;
        $invoice->paid_amount = $data['paid_amount'] ?? null;
        $invoice->paid_at = $data['paid_at'] ?? null;
        $invoice->save();
        

        // Jika invoice sudah dibayar, update langganan
        if (in_array($newStatus, ['Lunas', 'Selesai'])) {
            $this->updateLanggananAfterPayment($invoice);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice berhasil diperbarui'
        ]);

    } catch (\Exception $e) {
        Log::error('Error processing webhook', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Server error'
        ], 500);
    }
}

// Method helper untuk update langganan
private function updateLanggananAfterPayment($invoice)
{
    $langganan = $invoice->langganan;
    if (!$langganan) return;

    // Format tanggal invoice
    $tglInvoice = $invoice->tgl_invoice 
        ? Carbon::parse($invoice->tgl_invoice)->format('Y-m-d')
        : now()->format('Y-m-d');
    
    // Update tanggal invoice terakhir
    $langganan->tgl_invoice_terakhir = $tglInvoice;
    $langganan->save();
    
    // Update tanggal jatuh tempo dan status
    if (method_exists($langganan, 'updateTanggalJatuhTempo')) {
        $langganan->updateTanggalJatuhTempo($tglInvoice, $invoice->invoice_number);
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