<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;

class WebhookController extends Controller
{
    public function handleXenditCallback(Request $request)
    {
        // Log semua data yang diterima untuk debugging
        Log::info('Xendit Webhook Data', $request->all());

        // Ambil data penting dari webhook
        $externalId = $request->input('external_id'); // Nomor invoice Anda
        $xenditId = $request->input('id'); // ID Xendit
        $status = $request->input('status'); // Status dari Xendit

        // Temukan invoice berdasarkan nomor invoice
        $invoice = Invoice::where('invoice_number', $externalId)->first();

        if (!$invoice) {
            Log::error('Invoice tidak ditemukan: ' . $externalId);
            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        // Simpan xendit_id jika belum ada
        if (empty($invoice->xendit_id)) {
            $invoice->xendit_id = $xenditId;
        }

        // Misalnya, logika pembaruan status invoice
        if ($status == 'SETTLED') {
            $invoice->status_invoice = 'Lunas';
        } elseif ($status == 'EXPIRED') {
            $invoice->status_invoice = 'Kadaluarsa';
        } elseif ($status == 'PENDING') {
            $invoice->status_invoice = 'Menunggu Pembayaran';
        }
        $invoice->save();

        Log::info('Invoice updated', [
            'invoice_number' => $invoice->invoice_number,
            'new_status' => $invoice->status_invoice
        ]);

        // Kirim response berhasil        
        return response()->json(['status' => 'success']);
    }
}
