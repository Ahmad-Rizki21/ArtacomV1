<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    public function sendToXendit($id)
    {
        // Ambil data invoice berdasarkan ID
        $invoice = Invoice::find($id);
        set_time_limit(120);  // Menambah waktu eksekusi menjadi 120 detik

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        // Pastikan nomor telepon dalam format internasional (+62 untuk Indonesia)
        $phoneNumber = $this->formatPhoneNumber($invoice->no_telp);

        // Pilih API Key berdasarkan brand
        $apiKey = $this->getApiKeyByBrand($invoice->brand); // Ambil API key dari .env

        // URL Xendit API
        $url = env('XENDIT_API_URL');  // pastikan URL Xendit sudah benar

        // Data yang dikirim ke Xendit
        $data = [
            'external_id' => $invoice->invoice_number,
            'payer_email' => $invoice->email,
            'description' => "Pembayaran Invoice #{$invoice->invoice_number}",
            'amount' => (int) $invoice->total_harga,
            'customer' => [
                'given_names' => $invoice->pelanggan->nama,
                'email' => $invoice->email,
                'mobile_number' => $phoneNumber,  // Pastikan format telepon sudah benar
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['email', 'whatsapp'],  // Pastikan sudah benar
                'invoice_reminder' => ['email', 'whatsapp'],
                'invoice_expired' => ['email', 'whatsapp'],
                'invoice_paid' => ['email', 'whatsapp'],
            ]
        ];

        // Kirim permintaan ke Xendit
        $response = Http::withBasicAuth($apiKey, '')
            ->post($url, $data);

        // Log respons dari Xendit untuk debugging
        Log::info('Xendit Response:', ['response' => $response->json()]);

        // Simpan response ke database jika berhasil
        if ($response->successful()) {
            // Pastikan ada 'invoice_url' di dalam respons
            if ($response->has('invoice_url')) {
                $invoice->payment_link = $response['invoice_url'];
                $invoice->status_invoice = 'Menunggu Pembayaran';
                $invoice->save();
            }
        
            // Kembalikan response dengan informasi link pembayaran
            return response()->json([
                'message' => 'Invoice berhasil dikirim ke Xendit dan link disimpan.',
                'payment_link' => $invoice->payment_link
            ]);
        } else {
            // Log jika ada kesalahan
            Log::error('Failed to create invoice on Xendit:', ['error' => $response->json()]);
            return response()->json([
                'message' => 'Gagal mengirim invoice ke Xendit',
                'error' => $response->json()
            ], 500);
        }
    }

    /**
     * Format nomor telepon menjadi format internasional (+62 untuk Indonesia).
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Jika nomor telepon tidak dimulai dengan '+' atau '0', tambahkan prefix +62 (Indonesia)
        if (substr($phoneNumber, 0, 1) == '0') {
            return '+62' . substr($phoneNumber, 1); // Ubah nomor jadi format internasional
        } elseif (substr($phoneNumber, 0, 1) != '+') {
            return '+62' . $phoneNumber; // Jika tidak dimulai dengan +, tambahkan +62
        }

        return $phoneNumber; // Jika sudah dalam format internasional
    }

    /**
     * Mendapatkan API Key berdasarkan brand.
     *
     * @param string $brand
     * @return string
     */
    private function getApiKeyByBrand($brand)
    {
        if ($brand === "jakinet") {
            return env('XENDIT_API_KEY_JAKINET');  // Ambil API key untuk Jakinet dari .env
        } else {
            return env('XENDIT_API_KEY_JELANTIK');  // Ambil API key untuk Jelantik dari .env
        }
    }
}
