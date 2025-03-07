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
        'ajn-02' => 'Jakinet',
        'ajn-01' => 'Jelantik',
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
            $invoice = Invoice::where('xendit_external_id', $data['external_id'])->first();

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
     * @return array
     */
    public function createInvoice(Invoice $invoice)
    {
        try {
            // Dapatkan nama brand
            $brandName = $this->getBrandName($invoice->brand);
            
            // Pilih API key berdasarkan brand
            $apiKey = $this->getApiKeyByBrand($brandName);

            // Validasi data invoice
            $this->validateInvoiceData($invoice);

            // Siapkan payload untuk Xendit
            $data = $this->prepareXenditPayload($invoice);

            // Kirim permintaan ke Xendit
            $url = env('XENDIT_API_URL');
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(10)
                ->post($url, $data);

            // Log respons untuk debugging
            Log::info('Xendit Invoice Creation', [
                'invoice_number' => $invoice->invoice_number,
                'response_status' => $response->status(),
                'response_data' => $response->json()
            ]);

            // Proses hasil dari Xendit
            return $this->processXenditResponse($response, $invoice);

        } catch (Exception $e) {
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
        $brand = HargaLayanan::where('id_brand', $brandId)->value('brand');
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

        // Khusus untuk Nagrak atau Jelantik Nagrak
        if (strpos($brandName, 'nagrak') !== false) {
            return env('XENDIT_API_KEY_JAKINET');  // Menggunakan API key Jakinet untuk Jelantik (Nagrak)
        }
    
        // Untuk Jelantik
        if (strpos($brandName, 'jelantik') !== false) {
            return env('XENDIT_API_KEY_JELANTIK');
        }
    
        // Untuk Jakinet
        if (strpos($brandName, 'jakinet') !== false) {
            return env('XENDIT_API_KEY_JAKINET');
        }
    
        // Fallback ke Jakinet jika tidak dikenali
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

        // Periksa apakah invoice_url ada di response
        if (empty($responseData['invoice_url'])) {
            Log::error('Invoice URL tidak ditemukan', [
                'response' => $responseData
            ]);
            throw new Exception('Invoice URL tidak ditemukan di respons Xendit');
        }

        // Update invoice dengan informasi dari Xendit
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
     * @param string $brand
     * @return array|null
     */
    public function checkInvoiceStatus(string $xenditId, string $brand)
    {
        try {
            $apiKey = $this->getApiKeyByBrand($brand);
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