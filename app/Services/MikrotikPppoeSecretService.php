<?php

namespace App\Services;

use App\Models\MikrotikServer;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class MikrotikPppoeSecretService
{
    /**
     * The Mikrotik connection service instance.
     *
     * @var MikrotikConnectionService
     */
    protected $mikrotikService;

    /**
     * Create a new service instance.
     * Hapus logika koneksi dari sini.
     *
     * @param MikrotikConnectionService $mikrotikService
     */
    public function __construct(MikrotikConnectionService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Creates a new PPPoE secret on a specific MikroTik server.
     *
     * @param array $data The secret's data (name, password, profile, etc.).
     * @param MikrotikServer $server The target MikroTik server.
     * @return bool True on success, false on failure.
     */
    public function createSecret(array $data, MikrotikServer $server): bool
    {
        // Pindahkan logika koneksi ke sini, dengan server yang spesifik.
        if (!($client = $this->mikrotikService->connect($server))) {
            Log::error('Gagal koneksi ke Mikrotik saat akan membuat secret.', ['server' => $server->name]);
            return false;
        }

        try {
            // Cek apakah secret sudah ada atau belum
            $existingQuery = (new Query('/ppp/secret/print'))->where('name', $data['name']);
            $existing = $client->query($existingQuery)->read();

            if (!empty($existing)) {
                Log::warning('PPPoE secret dengan nama ini sudah ada, proses pembuatan dilewati.', [
                    'name' => $data['name'], 
                    'server' => $server->name
                ]);
                return true; // Anggap berhasil jika sudah ada
            }

            // Buat secret baru
            $createQuery = new Query('/ppp/secret/add');
            foreach ($data as $key => $value) {
                $createQuery->equal($key, $value);
            }

            $client->query($createQuery)->read();
            Log::info('Berhasil membuat PPPoE secret baru.', ['name' => $data['name'], 'server' => $server->name]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception saat membuat PPPoE secret: ' . $e->getMessage(), [
                'data' => $data,
                'server' => $server->name,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
