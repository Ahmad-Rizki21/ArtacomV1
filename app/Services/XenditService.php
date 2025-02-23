<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Invoice;

class XenditService
{
    public static function createInvoice(Invoice $invoice, $apiKey)
    {
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
                'mobile_number' => $invoice->no_telp,  // Pastikan nomor telepon dalam format internasional (+62 untuk Indonesia)
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

        // Simpan response ke database jika berhasil
        if ($response->successful()) {
            $invoice->payment_link = $response['invoice_url'];
            $invoice->status_invoice = 'Menunggu Pembayaran';
            $invoice->save();

            return [
                'status' => 'success',
                'invoice_url' => $invoice->payment_link
            ];
        } else {
            return [
                'status' => 'failed',
                'error' => $response->json(),
            ];
        }
    }
}
