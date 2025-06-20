<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;

class MikrotikPppoeSecretService
{
    protected $client;

    public function __construct(MikrotikConnectionService $connectionService)
    {
        $this->client = $connectionService->connect();

        if (!$this->client) {
            Log::error('Gagal koneksi ke Mikrotik di MikrotikPppoeSecretService');
        }
    }

    /**
     * Buat PPPoE Secret baru
     *
     * @param array $data
     * - name
     * - password
     * - profile
     * - remote-address
     * - service (default 'pppoe')
     * @return bool
     */
    public function createSecret(array $data)
    {
        if (!$this->client) {
            return false;
        }

        try {
            Log::info('Membuat PPPoE secret dengan data:', $data);

            // Cek secret sudah ada atau belum
            $queryCheck = new Query('/ppp/secret/print');
            $queryCheck->where('name', $data['name']);
            $existing = $this->client->query($queryCheck)->read();

            if (!empty($existing)) {
                Log::warning("PPPoE Secret dengan name {$data['name']} sudah ada, skip create.");
                return true;
            }

            $query = new Query('/ppp/secret/add');
            foreach ($data as $key => $value) {
                $query->equal($key, $value);
            }

            $response = $this->client->query($query)->read();

            Log::info('Response create PPPoE secret:', $response);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception createSecret: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return false;
        }
    }
}
