<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\HargaLayanan;
use Exception;

class XenditService
{
    private const BRAND_MAPPING = [
        'ajn-02' => 'Jelantik',
        'ajn-01' => 'Jakinet',
        'ajn-03' => 'Jelantik (Nagrak)'
    ];

    private const STATUS_MAP = [
        'PENDING' => 'Menunggu Pembayaran',
        'PAID' => 'Lunas',
        'SETTLED' => 'Selesai',
        'EXPIRED' => 'Kadaluarsa'
    ];

    /**
     * Memproses webhook yang diterima dari Xendit
     *
     * @param array $data
     * @return array
     */
    public function processWebhook(array $data): array
    {
        try {
            // Validasi data yang diterima
            if (empty($data['external_id']) || empty($data['status'])) {
                throw new Exception('Data webhook tidak lengkap');
            }

            // Cari invoice berdasarkan external_id yang diterima dari webhook
            $invoice = Invoice::where('xendit_external_id', $data['external_id'])
                             ->orWhere('invoice_number', $data['external_id'])
                             ->first();

            if (!$invoice) {
                throw new Exception('Invoice tidak ditemukan');
            }

            // Update status invoice berdasarkan status yang diterima dari Xendit
            $newStatus = self::STATUS_MAP[$data['status']] ?? 'Tidak Diketahui';
            $invoice->status_invoice = $newStatus;
            $invoice->xendit_id = $data['id'];
            $invoice->paid_amount = $data['paid_amount'] ?? null;
            $invoice->paid_at = $data['paid_at'] ?? null;
            $invoice->save();

            // Log hasil pembaruan status invoice
            Log::info('Invoice status updated from webhook', [
                'invoice_number' => $invoice->invoice_number,
                'new_status' => $newStatus
            ]);

            return [
                'status' => 'success',
                'message' => 'Invoice berhasil diperbarui'
            ];

        } catch (Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Membuat invoice di Xendit
     *
     * @param Invoice $invoice
     * @param string|null $traceId
     * @return array
     */
    public function createInvoice(Invoice $invoice, ?string $traceId = null): array
    {
        try {
            // Logging untuk debugging
            Log::info('XenditService::createInvoice called', [
                'invoice_number' => $invoice->invoice_number,
                'brand' => $invoice->brand,
                'trace_id' => $traceId ?? uniqid('direct_call_', true)
            ]);
            
            // Periksa apakah invoice sudah diproses
            if (!empty($invoice->payment_link) || !empty($invoice->xendit_id)) {
                return [
                    'status' => 'success',
                    'message' => 'Invoice sudah diproses sebelumnya',
                    'invoice_url' => $invoice->payment_link,
                    'xendit_id' => $invoice->xendit_id,
                    'external_id' => $invoice->xendit_external_id ?? $invoice->invoice_number
                ];
            }

            // Dapatkan nama brand dengan debug logging
            $brandName = $this->getBrandName($invoice->brand);
            
            // Debug logging untuk brand
            Log::info('Brand Name Lookup', [
                'input_brand_id' => $invoice->brand,
                'derived_brand_name' => $brandName
            ]);

            // Pilih API key berdasarkan brand
            $apiKey = $this->getApiKeyByBrand($invoice->brand, $brandName);

            // Validasi data invoice
            $this->validateInvoiceData($invoice);

            // Siapkan payload untuk Xendit
            $data = $this->prepareXenditPayload($invoice, $traceId);

            // Kirim permintaan ke Xendit dengan idempotency key
            $url = env('XENDIT_API_URL');
            $idempotencyKey = 'invoice_' . $invoice->invoice_number;
            
            Log::info('Sending request to Xendit with idempotency key', [
                'invoice_number' => $invoice->invoice_number,
                'idempotency_key' => $idempotencyKey,
                'api_key_used' => substr($apiKey, 0, 10) . '...',
                'trace_id' => $traceId
            ]);
            
            $response = Http::withBasicAuth($apiKey, '')
                ->withHeaders([
                    'Idempotency-Key' => $idempotencyKey
                ])
                ->timeout(15) // Increased timeout
                ->post($url, $data);

            // Log respons untuk debugging
            Log::info('Xendit Invoice Creation', [
                'invoice_number' => $invoice->invoice_number,
                'response_status' => $response->status(),
                'response_data' => $response->json(),
                'trace_id' => $traceId
            ]);

            // Proses hasil dari Xendit
            return $this->processXenditResponse($response, $invoice, $traceId);

        } catch (Exception $e) {
            Log::error('Xendit Invoice Creation Failed', [
                'invoice_number' => $invoice->invoice_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'trace_id' => $traceId
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
    // Logging untuk debugging
    Log::info('getBrandName Debug', [
        'brand_id' => $brandId,
        'brand_mapping' => self::BRAND_MAPPING
    ]);

    // Coba ambil dari database terlebih dahulu
    $brand = HargaLayanan::where('id_brand', $brandId)->value('brand');
    
    Log::info('Database Brand Check', [
        'database_brand' => $brand
    ]);

    // Prioritaskan brand dari database
    if ($brand) {
        return $brand;
    }

    // Jika tidak ada di database, gunakan mapping
    $finalBrand = self::BRAND_MAPPING[strtolower($brandId)] ?? $brandId;

    Log::info('Final Brand Name', [
        'final_brand' => $finalBrand
    ]);

    return $finalBrand;
}

    /**
     * Dapatkan API key berdasarkan brand
     *
     * @param string $brandId
     * @param string $brandName
     * @return string
     */
//     private function getApiKeyByBrand(string $brandId, string $brandName): string
// {
//     $brandId = strtolower($brandId);
//     $brandName = strtolower($brandName);

//     Log::info('API Key Selection Debug', [
//         'brand_id' => $brandId,
//         'brand_name' => $brandName,
//         'brand_mapping' => self::BRAND_MAPPING
//     ]);

//     // Debug print actual checking conditions
//     Log::info('Brand Checking Conditions', [
//         'is_ajn_03' => $brandId === 'ajn-03',
//         'is_ajn_01' => $brandId === 'ajn-01',
//         'is_ajn_02' => $brandId === 'ajn-02',
//         'contains_nagrak' => strpos($brandName, 'nagrak') !== false,
//         'contains_jelantik' => strpos($brandName, 'jelantik') !== false,
//         'contains_jakinet' => strpos($brandName, 'jakinet') !== false
//     ]);

//     // Khusus untuk Jelantik Nagrak, gunakan API key Jakinet
//     if ($brandId === 'ajn-03') {
//         Log::info('Returning API Key for Nagrak', ['api_key' => 'XENDIT_API_KEY_JAKINET']);
//         return env('XENDIT_API_KEY_JAKINET');  
//     }

//     // Untuk Jelantik (bukan Nagrak)
//     if ($brandId === 'ajn-01' && strpos($brandName, 'nagrak') === false) {
//         Log::info('Returning API Key for Jelantik', ['api_key' => 'XENDIT_API_KEY_JELANTIK']);
//         return env('XENDIT_API_KEY_JELANTIK');
//     }

//     // Untuk Jakinet
//     if ($brandId === 'ajn-02') {
//         Log::info('Returning API Key for Jakinet', ['api_key' => 'XENDIT_API_KEY_JAKINET']);
//         return env('XENDIT_API_KEY_JAKINET');
//     }

//     // Fallback ke Jakinet jika tidak dikenali
//     Log::warning('Fallback to Jakinet API Key', [
//         'brand_id' => $brandId, 
//         'brand_name' => $brandName
//     ]);
//     return env('XENDIT_API_KEY_JAKINET');
// }



private function getApiKeyByBrand(string $brandId, string $brandName): string
{
    $brandId = strtolower($brandId);
    $brandName = strtolower($brandName);

    // Jakinet (ajn-01) menggunakan API Jakinet
    if ($brandId === 'ajn-01') {
        return env('XENDIT_API_KEY_JAKINET');
    }

    // Jelantik (ajn-02) menggunakan API Jelantik
    if ($brandId === 'ajn-02') {
        return env('XENDIT_API_KEY_JELANTIK');
    }

    // Jelantik Nagrak (ajn-03) menggunakan API Jakinet
    if ($brandId === 'ajn-03') {
        return env('XENDIT_API_KEY_JAKINET');
    }

    // Fallback
    return env('XENDIT_API_KEY_JAKINET');
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

        if (!is_numeric($invoice->total_harga) || $invoice->total_harga <= 0) {
            throw new Exception('Total harga harus lebih dari 0 dan berupa angka');
        }
    }

    /**
     * Siapkan payload untuk Xendit
     *
     * @param Invoice $invoice
     * @param string|null $traceId
     * @return array
     */
    private function prepareXenditPayload(Invoice $invoice, ?string $traceId = null): array
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
            ],
            'metadata' => [
                'trace_id' => $traceId ?? uniqid('xendit_', true)
            ]
        ];
    }

    /**
     * Proses respons dari Xendit
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param Invoice $invoice
     * @param string|null $traceId
     * @return array
     * @throws Exception
     */
    private function processXenditResponse($response, Invoice $invoice, ?string $traceId = null): array
    {
        if (!$response->successful()) {
            Log::error('Xendit Invoice Creation Failed', [
                'response' => $response->json(),
                'status' => $response->status(),
                'trace_id' => $traceId
            ]);
            throw new Exception('Gagal membuat invoice di Xendit: ' . ($response->json()['message'] ?? 'Unknown error'));
        }

        $responseData = $response->json();

        // Periksa apakah invoice_url ada di response
        if (empty($responseData['invoice_url'])) {
            Log::error('Invoice URL tidak ditemukan', [
                'response' => $responseData,
                'trace_id' => $traceId
            ]);
            throw new Exception('Invoice URL tidak ditemukan di respons Xendit');
        }

        return [
            'status' => 'success',
            'invoice_url' => $responseData['invoice_url'],
            'xendit_id' => $responseData['id'],
            'external_id' => $responseData['external_id']
        ];
    }

    /**
     * Cek status invoice di Xendit
     *
     * @param string $xenditId
     * @param string $brand
     * @return array|null
     */
    public function checkInvoiceStatus(string $xenditId, string $brand)
    {
        try {
            $brandName = $this->getBrandName($brand);
            $apiKey = $this->getApiKeyByBrand($brand, $brandName);
            $url = "https://api.xendit.co/v2/invoices/{$xenditId}";

            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->get($url);

            if (!$response->successful()) {
                Log::error('Gagal memeriksa status invoice Xendit', [
                    'xenditId' => $xenditId,
                    'brand' => $brand,
                    'response' => $response->body()
                ]);
                return null;
            }

            $responseData = $response->json();
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