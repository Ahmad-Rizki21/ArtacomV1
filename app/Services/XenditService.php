<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\HargaLayanan;
use Exception;

class XenditService
{
    /**
     * Mapping brand untuk Xendit
     */
    private const BRAND_MAPPING = [
        'ajn-02' => 'Jakinet',
        'ajn-01' => 'Jelantik'
    ];

    /**
     * Status mapping Xendit
     */
    private const STATUS_MAP = [
        'PENDING' => 'Menunggu Pembayaran',
        'PAID' => 'Lunas',
        'SETTLED' => 'Selesai',
        'EXPIRED' => 'Kadaluarsa'
    ];

    /**
     * Membuat invoice di Xendit
     *
     * @param Invoice $invoice
     * @return array
     */
    public function createInvoice(Invoice $invoice)
    {
        try {
            // Ambil nama brand
            $brandName = $this->getBrandName($invoice->brand);
            
            // Pilih API key berdasarkan brand
            $apiKey = $this->getApiKeyByBrand($brandName);

            // URL Xendit API
            $url = env('XENDIT_API_URL');

            // Validasi data invoice
            $this->validateInvoiceData($invoice);

            // Data untuk dikirim ke Xendit
            $data = $this->prepareXenditPayload($invoice);

            // Kirim permintaan ke Xendit
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->post($url, $data);

            // Log respons untuk debugging
            Log::info('Xendit Invoice Creation Full Details', [
                'invoice_number' => $invoice->invoice_number,
                'brand' => $brandName,
                'response_status' => $response->status(),
                'response_data' => $response->json()
            ]);

            // Proses respons
            $result = $this->processXenditResponse($response, $invoice);

            // Log hasil proses
            Log::info('Xendit Invoice Creation Result', [
                'invoice_number' => $invoice->invoice_number,
                'result' => $result
            ]);

            return $result;

        } catch (Exception $e) {
            // Tangani kesalahan
            Log::error('Xendit Invoice Creation Failed', [
                'invoice_number' => $invoice->invoice_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }



    



    /**
     * Dapatkan nama brand
     *
     * @param string $brandId
     * @return string
     */
    private function getBrandName(string $brandId): string
    {
        // Cari nama brand di database
        $brand = HargaLayanan::where('id_brand', $brandId)->value('brand');
        
        // Gunakan mapping jika ada, atau kembalikan brand asli
        return self::BRAND_MAPPING[strtolower($brandId)] ?? $brand ?? $brandId;
    }

    /**
     * Dapatkan API key berdasarkan brand
     *
     * @param string $brandName
     * @return string
     * @throws Exception
     */
    private function getApiKeyByBrand(string $brandName): string
    {
        $brandName = strtolower($brandName);
        
        $apiKey = match($brandName) {
            'jakinet' => env('XENDIT_API_KEY_JAKINET'),
            'jelantik' => env('XENDIT_API_KEY_JELANTIK'),
            default => throw new Exception("API Key tidak ditemukan untuk brand: $brandName")
        };

        return $apiKey;
    }

    /**
     * Validasi data invoice sebelum dikirim
     *
     * @param Invoice $invoice
     * @throws Exception
     */
    private function validateInvoiceData(Invoice $invoice)
    {
        if (empty($invoice->email)) {
            throw new Exception('Email pelanggan tidak boleh kosong');
        }

        if ($invoice->total_harga <= 0) {
            throw new Exception('Total harga harus lebih dari 0');
        }
    }

    /**
     * Siapkan payload untuk Xendit
     *
     * @param Invoice $invoice
     * @return array
     */
    private function prepareXenditPayload(Invoice $invoice): array
    {
        return [
            'external_id' => $invoice->invoice_number,
            'payer_email' => $invoice->email,
            'description' => "Pembayaran Invoice #{$invoice->invoice_number}",
            'amount' => (int) $invoice->total_harga,
            'customer' => [
                'given_names' => $invoice->pelanggan->nama ?? 'Pelanggan',
                'email' => $invoice->email,
                'mobile_number' => $invoice->no_telp,
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['email', 'whatsapp'],
                'invoice_reminder' => ['email', 'whatsapp'],
                'invoice_expired' => ['email', 'whatsapp'],
                'invoice_paid' => ['email', 'whatsapp'],
            ]
        ];
    }

    /**
     * Proses respons dari Xendit
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param Invoice $invoice
     * @return array
     * @throws Exception
     */
    private function processXenditResponse($response, Invoice $invoice): array
{
    if (!$response->successful()) {
        Log::error('Xendit Invoice Creation Failed', [
            'response' => $response->json(),
            'status' => $response->status()
        ]);
        throw new Exception('Gagal membuat invoice di Xendit');
    }

    $responseData = $response->json();

    // Periksa keberadaan invoice URL
    if (empty($responseData['invoice_url'])) {
        Log::error('Invoice URL tidak ditemukan', [
            'response' => $responseData
        ]);
        throw new Exception('Invoice URL tidak ditemukan di respons Xendit');
    }

    // Update invoice dengan informasi Xendit
    Log::info('Mengupdate status invoice di database', ['invoice_number' => $invoice->invoice_number, 'status' => $responseData['status']]);

    // Memastikan status diupdate
    $invoice->update([
        'payment_link' => $responseData['invoice_url'],
        'xendit_id' => $responseData['id'],
        'xendit_external_id' => $responseData['external_id'],
        'status_invoice' => self::STATUS_MAP[$responseData['status']] ?? 'Menunggu Pembayaran'
    ]);

    return [
        'status' => 'success',
        'invoice_url' => $responseData['invoice_url'],
        'xendit_id' => $responseData['id']
    ];
}


    /**
     * Cek status invoice di Xendit
     *
     * @param string $xenditId
     * @return string|null
     */
    // public function checkInvoiceStatus(string $xenditId)
    // {
    //     try {
    //         // Ambil URL dari environment
    //         $url = env('XENDIT_API_URL') . "/{$xenditId}";

    //         // Pilih API key (anda perlu menambahkan logika pemilihan API key)
    //         $apiKey = env('XENDIT_API_KEY_JAKINET'); // Sesuaikan dengan kebutuhan

    //         // Kirim request ke Xendit
    //         $response = Http::withBasicAuth($apiKey, '')
    //             ->timeout(10)
    //             ->get($url);

    //         // Periksa respons
    //         if (!$response->successful()) {
    //             Log::error('Gagal memeriksa status invoice Xendit', [
    //                 'xendit_id' => $xenditId,
    //                 'response' => $response->body()
    //             ]);
    //             return null;
    //         }

    //         // Ambil status dari respons
    //         $responseData = $response->json();
    //         $xenditStatus = $responseData['status'] ?? null;

    //         // Map status ke status internal
    //         $mappedStatus = self::STATUS_MAP[$xenditStatus] ?? 'Tidak Diketahui';

    //         Log::info('Status Invoice Xendit', [
    //             'xendit_id' => $xenditId,
    //             'xendit_status' => $xenditStatus,
    //             'mapped_status' => $mappedStatus
    //         ]);

    //         return $mappedStatus;

    //     } catch (Exception $e) {
    //         Log::error('Kesalahan memeriksa status invoice', [
    //             'xendit_id' => $xenditId,
    //             'error' => $e->getMessage()
    //         ]);

    //         return null;
    //     }
    // }

    public function checkInvoiceStatus(string $xenditId, string $brand)
{
    try {
        // Pilih API key berdasarkan brand
        $apiKey = match(strtolower($brand)) {
            'jakinet' => env('XENDIT_API_KEY_JAKINET'),
            'jelantik' => env('XENDIT_API_KEY_JELANTIK'),
            default => throw new Exception("API Key tidak ditemukan untuk brand: $brand")
        };

        // URL endpoint Xendit
        $url = "https://api.xendit.co/v2/invoices/{$xenditId}";

        // Kirim request ke Xendit
        $response = Http::withBasicAuth($apiKey, '')
            ->timeout(10)
            ->get($url);

        // Periksa response
        if (!$response->successful()) {
            Log::error('Gagal memeriksa status invoice Xendit', [
                'xenditId' => $xenditId,
                'brand' => $brand,
                'response' => $response->body()
            ]);
            return null;
        }

        // Ambil data respons
        $responseData = $response->json();

        // Log response untuk debugging
        Log::info('Xendit Invoice Status Check', [
            'xendit_id' => $xenditId,
            'brand' => $brand,
            'status' => $responseData['status']
        ]);

        return $responseData;

    } catch (Exception $e) {
        Log::error('Kesalahan memeriksa status invoice', [
            'xendit_id' => $xenditId,
            'brand' => $brand,
            'error' => $e->getMessage()
        ]);

        return null;
    }
}

}